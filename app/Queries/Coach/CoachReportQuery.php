<?php

namespace App\Queries\Coach;

use App\Models\ScheduledWorkout;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Database\Eloquent\Builder;

class CoachReportQuery
{
    public static function athletes(int $coachId, string $from, string $to, string $search = ''): Builder
    {
        $scheduledScope = fn (Builder $query) => $query
            ->where('coach_id', $coachId)
            ->whereDate('scheduled_for', '>=', $from)
            ->whereDate('scheduled_for', '<=', $to);
        $logScope = fn (Builder $query) => $query->whereHas('scheduledWorkout', $scheduledScope);

        return User::query()
            ->whereHas('athleteAssignments', fn (Builder $query) => $query
                ->where('coach_id', $coachId)
                ->where('status', 'active'))
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->withCount([
                'scheduledWorkouts as scheduled_count' => $scheduledScope,
                'workoutLogs as completed_count' => fn (Builder $query) => $logScope($query)->where('status', 'completed'),
                'workoutLogs as partial_count' => fn (Builder $query) => $logScope($query)->where('status', 'partial'),
                'workoutLogs as missed_count' => fn (Builder $query) => $logScope($query)->where('status', 'missed'),
            ])
            ->withAvg(['workoutLogs as average_rpe' => fn (Builder $query) => $logScope($query)], 'rpe')
            ->withSum(['workoutLogs as duration_minutes' => fn (Builder $query) => $logScope($query)], 'duration_minutes')
            ->orderBy('name');
    }

    public static function summary(int $coachId, string $from, string $to): array
    {
        $workouts = ScheduledWorkout::query()
            ->where('coach_id', $coachId)
            ->whereDate('scheduled_for', '>=', $from)
            ->whereDate('scheduled_for', '<=', $to);
        $logs = WorkoutLog::query()->whereHas('scheduledWorkout', fn (Builder $query) => $query
            ->where('coach_id', $coachId)
            ->whereDate('scheduled_for', '>=', $from)
            ->whereDate('scheduled_for', '<=', $to));

        $scheduled = (clone $workouts)->count();
        $completed = (clone $logs)->where('status', 'completed')->count();
        $partial = (clone $logs)->where('status', 'partial')->count();
        $missed = (clone $logs)->where('status', 'missed')->count();

        return [
            'scheduled' => $scheduled,
            'completed' => $completed,
            'partial' => $partial,
            'missed' => $missed,
            'overdue' => (clone $workouts)
                ->where('scheduled_for', '<', now())
                ->where('status', 'scheduled')
                ->whereDoesntHave('logs')
                ->count(),
            'completion_rate' => $scheduled > 0 ? round(($completed / $scheduled) * 100, 1) : 0,
            'average_rpe' => round((float) ((clone $logs)->whereNotNull('rpe')->avg('rpe') ?? 0), 1),
            'duration_minutes' => (int) ((clone $logs)->sum('duration_minutes') ?? 0),
        ];
    }
}
