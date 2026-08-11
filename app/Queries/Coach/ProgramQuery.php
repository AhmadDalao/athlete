<?php

namespace App\Queries\Coach;

use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProgramQuery
{
    /** @param array{search?: string, status?: string} $filters */
    public function build(User $coach, array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');

        return TrainingProgram::query()
            ->where('coach_id', $coach->id)
            ->withCount(['sessions', 'assignments'])
            ->with(['assignments.scheduledWorkouts.logs'])
            ->when(in_array($status, ['draft', 'active', 'archived'], true), fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('goal', 'like', "%{$search}%")));
    }
}
