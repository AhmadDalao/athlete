<?php

namespace App\Services;

use App\Models\PersonalRecord;
use App\Models\ProgressEntry;
use App\Models\ProgressPhoto;
use App\Models\ScheduledWorkout;
use App\Models\User;
use Carbon\CarbonImmutable;

class AthleteProgressSummaryService
{
    /** @return array<string, mixed> */
    public function forCoach(User $coach, User $athlete, ?string $from = null, ?string $to = null): array
    {
        $end = CarbonImmutable::parse($to ?: today()->toDateString())->endOfDay();
        $start = CarbonImmutable::parse($from ?: $end->subDays(89)->toDateString())->startOfDay();
        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        $workouts = ScheduledWorkout::query()
            ->where('athlete_id', $athlete->id)
            ->whereBetween('scheduled_for', [$start, $end])
            ->with('executionLog.setLogs')
            ->orderBy('scheduled_for')
            ->get();
        $logs = $workouts->pluck('executionLog')->filter();
        $sets = $logs->flatMap->setLogs;
        $completed = $workouts->where('status', 'completed')->count();
        $total = $workouts->count();
        $completedSets = $sets->whereNotNull('completed_at')->count();
        $totalSets = $sets->count();

        $checkIns = ProgressEntry::query()
            ->where('athlete_id', $athlete->id)
            ->whereBetween('logged_on', [$start->toDateString(), $end->toDateString()])
            ->orderBy('logged_on')
            ->get();

        return [
            'period' => ['from' => $start->toDateString(), 'to' => $end->toDateString()],
            'adherence' => [
                'completed' => $completed,
                'total' => $total,
                'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
                'partial' => $workouts->where('status', 'partial')->count(),
                'missed' => $workouts->whereIn('status', ['missed', 'skipped'])->count(),
            ],
            'sets' => [
                'completed' => $completedSets,
                'total' => $totalSets,
                'percent' => $totalSets > 0 ? (int) round(($completedSets / $totalSets) * 100) : 0,
                'volume' => round((float) $sets->whereNotNull('completed_at')->sum(
                    fn ($set): float => (float) $set->actual_reps * (float) $set->actual_load
                ), 2),
            ],
            'averages' => [
                'rpe' => $logs->whereNotNull('rpe')->isNotEmpty() ? round((float) $logs->whereNotNull('rpe')->avg('rpe'), 1) : null,
                'duration_minutes' => $logs->whereNotNull('duration_minutes')->isNotEmpty()
                    ? (int) round((float) $logs->whereNotNull('duration_minutes')->avg('duration_minutes'))
                    : null,
                'energy' => $checkIns->whereNotNull('energy')->isNotEmpty() ? round((float) $checkIns->whereNotNull('energy')->avg('energy'), 1) : null,
                'sleep_quality' => $checkIns->whereNotNull('sleep_quality')->isNotEmpty() ? round((float) $checkIns->whereNotNull('sleep_quality')->avg('sleep_quality'), 1) : null,
                'soreness' => $checkIns->whereNotNull('soreness')->isNotEmpty() ? round((float) $checkIns->whereNotNull('soreness')->avg('soreness'), 1) : null,
                'hydration_ml' => $checkIns->whereNotNull('hydration')->isNotEmpty() ? (int) round((float) $checkIns->whereNotNull('hydration')->avg('hydration')) : null,
            ],
            'counts' => [
                'check_ins' => $checkIns->count(),
                'records' => PersonalRecord::query()->where('athlete_id', $athlete->id)->whereBetween('achieved_on', [$start->toDateString(), $end->toDateString()])->count(),
                'photos' => ProgressPhoto::query()->where('athlete_id', $athlete->id)->whereBetween('taken_on', [$start->toDateString(), $end->toDateString()])->count(),
            ],
            'workout_trend' => $workouts->map(function (ScheduledWorkout $workout): array {
                $sets = $workout->executionLog?->setLogs ?? collect();
                $completedSets = $sets->whereNotNull('completed_at');

                return [
                    'date' => $workout->scheduled_for?->toDateString(),
                    'status' => $workout->status,
                    'rpe' => $workout->executionLog?->rpe,
                    'duration_minutes' => $workout->executionLog?->duration_minutes,
                    'sets_completed' => $completedSets->count(),
                    'sets_total' => $sets->count(),
                    'volume' => round((float) $completedSets->sum(
                        fn ($set): float => (float) $set->actual_reps * (float) $set->actual_load
                    ), 2),
                ];
            })->values(),
            'check_in_trend' => $checkIns->map(fn (ProgressEntry $entry): array => [
                'date' => $entry->logged_on?->toDateString(),
                'weight_kg' => $entry->weight !== null ? (float) $entry->weight : null,
                'energy' => $entry->energy,
                'sleep_quality' => $entry->sleep_quality,
                'soreness' => $entry->soreness,
                'hydration_ml' => $entry->hydration,
            ])->values(),
        ];
    }
}
