<?php

use App\Services\MembershipStatusAuditor;
use App\Services\Whoop\WhoopSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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

Schedule::command('throughline:memberships:audit')->dailyAt('00:10');
Schedule::command('throughline:whoop:sync')->hourly();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
