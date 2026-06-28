<?php

use App\Enums\RoleName;
use App\Models\User;
use App\Services\MembershipStatusAuditor;
use App\Services\Whoop\WhoopSyncService;
use App\Services\Whoop\WhoopWebhookService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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

Schedule::command('throughline:memberships:audit')->dailyAt('00:10');
Schedule::command('throughline:whoop:sync')->hourly();
Schedule::command('throughline:whoop:webhooks:process')->everyFiveMinutes();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
