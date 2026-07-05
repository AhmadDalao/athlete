<?php

use App\Enums\DeviceProvider;
use App\Enums\RoleName;
use App\Models\AthleteCheckIn;
use App\Models\DeviceConnection;
use App\Models\DeviceMetricIngest;
use App\Models\MetricSnapshot;
use App\Models\User;
use App\Services\MembershipStatusAuditor;
use App\Services\Whoop\WhoopSyncService;
use App\Services\Whoop\WhoopWebhookService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('throughline:memberships:audit', function (MembershipStatusAuditor $auditor) {
    $summary = $auditor->run();

    $this->info("Processed {$summary['processed']} memberships.");
    $this->info("Updated {$summary['updated']} membership statuses.");

    foreach ($summary['breakdown'] as $status => $count) {
        $this->line(" - {$status}: {$count}");
    }
})->purpose('Audit membership statuses and lifecycle transitions');

Artisan::command('throughline:whoop:sync {--connection_id=} {--lookback_days=}', function (WhoopSyncService $syncService) {
    $connectionId = $this->option('connection_id');
    $lookbackDays = $this->option('lookback_days');
    $summary = $syncService->syncEligibleConnections(
        $connectionId !== null ? (int) $connectionId : null,
        $lookbackDays !== null ? (int) $lookbackDays : null,
    );

    $this->info("Processed {$summary['processed']} WHOOP connection(s).");
    $this->info("Synced {$summary['synced']} WHOOP connection(s).");
    $this->info("Failed {$summary['failed']} WHOOP connection(s).");

    foreach ($summary['errors'] as $error) {
        $this->line(" - {$error}");
    }
})->purpose('Sync WHOOP OAuth connections into normalized metric snapshots');

Artisan::command('throughline:whoop:webhooks:process {--limit=25}', function (WhoopWebhookService $webhooks) {
    $limit = max((int) $this->option('limit'), 1);
    $summary = $webhooks->processPendingEvents($limit);

    $this->info("Processed {$summary['processed']} WHOOP webhook event(s).");
    $this->info("Synced {$summary['synced']} WHOOP webhook event(s).");
    $this->info("Ignored {$summary['ignored']} WHOOP webhook event(s).");
    $this->info("Failed {$summary['failed']} WHOOP webhook event(s).");

    foreach ($summary['errors'] as $error) {
        $this->line(" - {$error}");
    }
})->purpose('Process signed WHOOP webhook events and trigger targeted reconciliation syncs');

Artisan::command('throughline:security:lock-demo-users {--admin-email=} {--admin-name=} {--password=}', function () {
    $adminEmail = trim((string) ($this->option('admin-email') ?: 'admin@athlete.ahmaddalao.com'));
    $adminName = trim((string) ($this->option('admin-name') ?: 'Ahmad Dalao'));
    $plainPassword = (string) ($this->option('password') ?: Str::password(24, letters: true, numbers: true, symbols: true, spaces: false));

    if ($adminEmail === '') {
        $this->error('Admin email cannot be blank.');

        return self::FAILURE;
    }

    if ($adminName === '') {
        $this->error('Admin name cannot be blank.');

        return self::FAILURE;
    }

    $demoEmails = [
        'admin@throughline.test',
        'coach@throughline.test',
        'coach2@throughline.test',
        'athlete1@throughline.test',
        'athlete2@throughline.test',
        'athlete3@throughline.test',
    ];

    $ownerAdmin = User::query()->firstOrNew([
        'email' => Str::lower($adminEmail),
    ]);

    $ownerAdmin->fill([
        'name' => $adminName,
        'password' => Hash::make($plainPassword),
        'position' => $ownerAdmin->position ?: 'Owner / General Manager',
        'preferred_contact_method' => $ownerAdmin->preferred_contact_method ?: 'email',
        'registration_channel' => $ownerAdmin->registration_channel ?: 'email',
    ]);
    $ownerAdmin->email_verified_at ??= now();
    $ownerAdmin->save();
    $ownerAdmin->syncRoles([RoleName::Owner, RoleName::Admin]);
    $ownerAdmin->syncPermissions([], $ownerAdmin);
    $ownerAdmin->tokens()->delete();
    $ownerAdmin->forceFill(['remember_token' => null])->save();

    $rotated = 0;

    User::query()
        ->whereIn('email', $demoEmails)
        ->where('email', '!=', $ownerAdmin->email)
        ->get()
        ->each(function (User $user) use (&$rotated): void {
            $user->forceFill([
                'password' => Hash::make(Str::password(32, letters: true, numbers: true, symbols: true, spaces: false)),
                'remember_token' => null,
            ])->save();

            $user->tokens()->delete();
            $rotated++;
        });

    $this->info("Owner admin: {$ownerAdmin->email}");
    $this->line("Name: {$ownerAdmin->name}");
    $this->line("Password: {$plainPassword}");
    $this->line("Demo users rotated: {$rotated}");
})->purpose('Create a real owner admin and rotate all seeded demo credentials');

Artisan::command('throughline:wearables:purge-demo-data {--apply : Delete records instead of only reporting counts} {--include-check-ins : Also delete seeded/demo manual progress check-ins} {--email=* : Limit cleanup to specific user email(s)}', function () {
    $apply = (bool) $this->option('apply');
    $includeCheckIns = (bool) $this->option('include-check-ins');
    $emails = collect((array) $this->option('email'))
        ->map(fn ($email): string => Str::lower(trim((string) $email)))
        ->filter()
        ->values();

    $demoProviders = [
        DeviceProvider::Garmin->value,
        DeviceProvider::Strava->value,
        DeviceProvider::Oura->value,
    ];

    $connectionQuery = DeviceConnection::query()
        ->where(function ($query) use ($demoProviders): void {
            $query
                ->whereIn('provider', $demoProviders)
                ->orWhere(function ($whoopQuery): void {
                    $whoopQuery
                        ->where('provider', DeviceProvider::Whoop->value)
                        ->where(function ($seededWhoopQuery): void {
                            $seededWhoopQuery->where('external_user_id', 'like', 'whoop-athlete-%');
                        });
                });
        })
        ->when($emails->isNotEmpty(), function ($query) use ($emails): void {
            $query->whereHas('user', fn ($userQuery) => $userQuery->whereIn(DB::raw('lower(email)'), $emails->all()));
        });

    $connectionIds = (clone $connectionQuery)->pluck('id');
    $snapshotCount = MetricSnapshot::query()
        ->whereIn('device_connection_id', $connectionIds)
        ->count();
    $ingestCount = DeviceMetricIngest::query()
        ->whereIn('device_connection_id', $connectionIds)
        ->count();
    $connectionCount = $connectionIds->count();
    $checkInCount = 0;

    $checkInQuery = AthleteCheckIn::query()
        ->whereHas('user', function ($query) use ($emails): void {
            $query->where(function ($demoQuery): void {
                $demoQuery
                    ->where('email', 'like', '%@throughline.test')
                    ->orWhere('email', 'like', 'codex.%@%')
                    ->orWhere('email', 'like', 'demo.%@%');
            });

            if ($emails->isNotEmpty()) {
                $query->whereIn(DB::raw('lower(email)'), $emails->all());
            }
        });

    if ($includeCheckIns) {
        $checkInCount = (clone $checkInQuery)->count();
    }

    $this->line($apply ? 'Mode: apply' : 'Mode: dry run');
    $this->line("Demo device connections: {$connectionCount}");
    $this->line("Demo metric ingests: {$ingestCount}");
    $this->line("Demo metric snapshots: {$snapshotCount}");
    $this->line("Demo manual check-ins: {$checkInCount}");

    if (! $apply) {
        $this->warn('No records deleted. Re-run with --apply to purge.');

        return self::SUCCESS;
    }

    DB::transaction(function () use ($connectionIds, $includeCheckIns, $checkInQuery): void {
        if ($includeCheckIns) {
            $checkInQuery->delete();
        }

        DeviceConnection::query()
            ->whereIn('id', $connectionIds)
            ->delete();
    });

    $this->info('Demo wearable data purged.');
    $this->line('Live mobile providers preserved: health_connect, apple_health.');
    $this->line('Real WHOOP OAuth rows with tokens are preserved.');

    return self::SUCCESS;
})->purpose('Remove seeded/demo wearable snapshots so only live synced data remains');

Schedule::command('throughline:memberships:audit')->dailyAt('00:10');
Schedule::command('throughline:whoop:sync')->hourly();
Schedule::command('throughline:whoop:webhooks:process')->everyFiveMinutes();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
