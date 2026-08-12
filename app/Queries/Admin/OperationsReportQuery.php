<?php

namespace App\Queries\Admin;

use App\Models\AthleteInvitation;
use App\Models\OrganizationMembership;
use App\Models\ProgressEntry;
use App\Models\ScheduledWorkout;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Models\WorkoutLog;
use Illuminate\Database\Eloquent\Builder;

class OperationsReportQuery
{
    public static function coaches(int $organizationId, string $from, string $to, string $search = ''): Builder
    {
        $workoutScope = fn (Builder $query) => $query
            ->where('organization_id', $organizationId)
            ->whereDate('scheduled_for', '>=', $from)
            ->whereDate('scheduled_for', '<=', $to);

        return User::query()
            ->whereHas('organizationMemberships', fn (Builder $query) => $query
                ->where('organization_id', $organizationId)
                ->where('role', 'coach')
                ->where('status', 'active'))
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->withCount([
                'coachAssignments as athlete_count' => fn (Builder $query) => $query->where('organization_id', $organizationId)->where('status', 'active'),
                'coachPrograms as active_program_count' => fn (Builder $query) => $query->where('organization_id', $organizationId)->where('status', 'active'),
                'coachedScheduledWorkouts as scheduled_count' => $workoutScope,
                'coachedScheduledWorkouts as completed_count' => fn (Builder $query) => $workoutScope($query)
                    ->whereHas('logs', fn (Builder $query) => $query->where('status', 'completed')),
                'coachedScheduledWorkouts as partial_count' => fn (Builder $query) => $workoutScope($query)
                    ->whereHas('logs', fn (Builder $query) => $query->where('status', 'partial')),
                'coachedScheduledWorkouts as missed_count' => fn (Builder $query) => $workoutScope($query)
                    ->whereHas('logs', fn (Builder $query) => $query->where('status', 'missed')),
                'coachedScheduledWorkouts as overdue_count' => fn (Builder $query) => $query
                    ->where('scheduled_for', '<', now())
                    ->where('status', 'scheduled')
                    ->whereDoesntHave('logs'),
            ])
            ->orderBy('name');
    }

    public static function summary(int $organizationId, string $from, string $to): array
    {
        $workouts = ScheduledWorkout::query()
            ->forOrganization($organizationId)
            ->whereDate('scheduled_for', '>=', $from)
            ->whereDate('scheduled_for', '<=', $to);
        $logs = WorkoutLog::query()
            ->forOrganization($organizationId)
            ->whereHas('scheduledWorkout', fn (Builder $query) => $query
                ->whereDate('scheduled_for', '>=', $from)
                ->whereDate('scheduled_for', '<=', $to));
        $scheduled = (clone $workouts)->count();
        $completed = (clone $logs)->where('status', 'completed')->count();

        return [
            'coaches' => OrganizationMembership::query()->where('organization_id', $organizationId)->where('role', 'coach')->where('status', 'active')->count(),
            'athletes' => OrganizationMembership::query()->where('organization_id', $organizationId)->where('role', 'athlete')->where('status', 'active')->count(),
            'programs' => TrainingProgram::query()->forOrganization($organizationId)->where('status', 'active')->count(),
            'open_invites' => AthleteInvitation::query()->forOrganization($organizationId)->where('status', 'pending')->count(),
            'scheduled' => $scheduled,
            'completed' => $completed,
            'partial' => (clone $logs)->where('status', 'partial')->count(),
            'missed' => (clone $logs)->where('status', 'missed')->count(),
            'overdue' => (clone $workouts)->where('scheduled_for', '<', now())->where('status', 'scheduled')->whereDoesntHave('logs')->count(),
            'completion_rate' => $scheduled > 0 ? round(($completed / $scheduled) * 100, 1) : 0,
            'check_ins' => ProgressEntry::query()->forOrganization($organizationId)->whereDate('logged_on', '>=', $from)->whereDate('logged_on', '<=', $to)->count(),
        ];
    }
}
