<?php

namespace App\Queries\Athlete;

use App\Models\ProgramAssignment;
use App\Models\ScheduledWorkout;
use Illuminate\Database\Eloquent\Builder;

class AthleteWorkspaceQuery
{
    public static function assignments(int $athleteId, string $status = 'current'): Builder
    {
        return ProgramAssignment::query()
            ->where('athlete_id', $athleteId)
            ->when(
                $status === 'current',
                fn (Builder $query) => $query->whereIn('status', ['active', 'paused']),
                fn (Builder $query) => $status !== 'all' ? $query->where('status', $status) : $query,
            )
            ->with([
                'program.coach',
                'program.phases',
                'scheduledWorkouts.session.prescribedExercises',
                'scheduledWorkouts.executionLog.setLogs',
            ])
            ->latest('starts_on');
    }

    public static function schedule(
        int $athleteId,
        ?string $from = null,
        ?string $to = null,
    ): Builder {
        return ScheduledWorkout::query()
            ->where('athlete_id', $athleteId)
            ->when($from, fn (Builder $query) => $query->whereDate('scheduled_for', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('scheduled_for', '<=', $to))
            ->with([
                'assignment.program.coach',
                'session.prescribedExercises',
                'executionLog.setLogs',
            ])
            ->orderBy('scheduled_for');
    }

    public static function canOpenAssignment(ProgramAssignment $assignment, int $athleteId): bool
    {
        return $assignment->athlete_id === $athleteId;
    }

    public static function canOpenWorkout(ScheduledWorkout $workout, int $athleteId): bool
    {
        return $workout->athlete_id === $athleteId
            && $workout->assignment()->where('athlete_id', $athleteId)->exists();
    }
}
