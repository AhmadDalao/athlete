<?php

use App\Models\ProgramAssignment;
use App\Services\ProgramPersonalizationService;
use Illuminate\Support\Facades\Artisan;

Artisan::command('throughline:about', function (): void {
    $this->info('Throughline clean coaching MVP');
});

Artisan::command('throughline:isolate-program-assignments {--execute : Apply the conversion instead of reporting it}', function (ProgramPersonalizationService $personalization): int {
    $query = ProgramAssignment::query()->whereHas('program', fn ($query) => $query->where('is_template', true));
    $count = $query->count();

    if (! $this->option('execute')) {
        $this->info("Dry run: {$count} assignment(s) still reference reusable presets.");
        $this->line('Run again with --execute after reviewing the production backup.');

        return self::SUCCESS;
    }

    $converted = 0;
    $query->orderBy('id')->eachById(function (ProgramAssignment $assignment) use ($personalization, &$converted): void {
        $personalization->isolateExistingAssignment($assignment);
        $converted++;
        $this->line("Isolated assignment {$assignment->id}.");
    });
    $this->info("Converted {$converted} assignment(s) to athlete-specific plans.");

    return self::SUCCESS;
})->purpose('Dry-run or isolate existing shared program assignments');
