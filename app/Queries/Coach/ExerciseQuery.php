<?php

namespace App\Queries\Coach;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ExerciseQuery
{
    /** @param array{search?: string, status?: string, ownership?: string} $filters */
    public function build(User $coach, array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $ownership = (string) ($filters['ownership'] ?? 'all');

        return Exercise::query()
            ->with('owner')
            ->where(fn (Builder $query) => $query->where('owner_id', $coach->id)->orWhere('is_shared', true))
            ->when(in_array($status, ['active', 'archived'], true), fn (Builder $query) => $query->where('status', $status))
            ->when($ownership === 'mine', fn (Builder $query) => $query->where('owner_id', $coach->id))
            ->when($ownership === 'shared', fn (Builder $query) => $query->where('is_shared', true))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('section', 'like', "%{$search}%")
                ->orWhere('movement_type', 'like', "%{$search}%")));
    }
}
