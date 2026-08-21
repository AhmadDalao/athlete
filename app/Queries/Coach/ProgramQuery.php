<?php

namespace App\Queries\Coach;

use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProgramQuery
{
    /** @param array{search?: string, status?: string, kind?: string} $filters */
    public function build(User $coach, array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $kind = (string) ($filters['kind'] ?? 'preset');

        return TrainingProgram::query()
            ->where('coach_id', $coach->id)
            ->withCount(['sessions', 'assignments', 'athletePlans as athlete_plans_count'])
            ->with(['assignments.scheduledWorkouts.logs', 'athletePlans.assignments.scheduledWorkouts.logs'])
            ->when($kind === 'preset', fn (Builder $query) => $query->where('is_template', true))
            ->when($kind === 'athlete_plan', fn (Builder $query) => $query->where('is_template', false)->with('athlete'))
            ->when(in_array($status, ['draft', 'active', 'archived'], true), fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('goal', 'like', "%{$search}%")));
    }
}
