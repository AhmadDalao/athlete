<?php

namespace App\Queries\Coach;

use App\Models\ScheduledWorkout;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ScheduleQuery
{
    /** @param array{search?: string, from?: string, to?: string, status?: string, athlete_id?: int|string|null} $filters */
    public function build(User $coach, array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? 'all');
        $athleteId = filter_var($filters['athlete_id'] ?? null, FILTER_VALIDATE_INT) ?: null;

        return ScheduledWorkout::query()
            ->where('coach_id', $coach->id)
            ->with(['athlete', 'session.program', 'assignment'])
            ->withCount('logs')
            ->when(filled($filters['from'] ?? null), fn (Builder $query) => $query->whereDate('scheduled_for', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? null), fn (Builder $query) => $query->whereDate('scheduled_for', '<=', $filters['to']))
            ->when(in_array($status, ['scheduled', 'completed', 'partial', 'missed', 'skipped', 'cancelled'], true), fn (Builder $query) => $query->where('status', $status))
            ->when($athleteId, fn (Builder $query) => $query->where('athlete_id', $athleteId))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereHas('athlete', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                ->orWhereHas('session', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
                ->orWhereHas('session.program', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))));
    }
}
