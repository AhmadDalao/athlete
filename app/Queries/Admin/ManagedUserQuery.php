<?php

namespace App\Queries\Admin;

use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ManagedUserQuery
{
    public static function visibleTo(User $actor, string $role = 'all'): Builder
    {
        if ($actor->isPlatformAdmin()) {
            return self::withOperationalCounts(User::query(), null)
                ->when(self::isGlobalRole($role), fn (Builder $query) => $query->where('role', $role));
        }

        return self::forOrganization(self::organizationId($actor), $role);
    }

    public static function forOrganization(int $organizationId, string $role = 'all'): Builder
    {
        $query = User::query()
            ->whereHas('organizationMemberships', fn (Builder $query) => $query
                ->where('organization_id', $organizationId))
            ->with(['organizationMemberships' => fn ($query) => $query
                ->where('organization_id', $organizationId)]);

        if ($role !== 'all') {
            $membershipRoles = match ($role) {
                'owner' => ['organization_owner'],
                'admin' => ['organization_admin'],
                'coach' => ['coach'],
                'athlete' => ['athlete'],
                default => [],
            };

            $query->whereHas('organizationMemberships', fn (Builder $query) => $query
                ->where('organization_id', $organizationId)
                ->whereIn('role', $membershipRoles ?: ['__invalid__']));
        }

        return self::withOperationalCounts($query, $organizationId);
    }

    public static function findVisibleOrFail(User $actor, int $userId): User
    {
        return self::visibleTo($actor)->findOrFail($userId);
    }

    public static function membership(User $actor, User $user): ?OrganizationMembership
    {
        if ($actor->isPlatformAdmin()) {
            return null;
        }

        $organizationId = self::organizationId($actor);

        if ($user->relationLoaded('organizationMemberships')) {
            return $user->organizationMemberships
                ->firstWhere('organization_id', $organizationId)
                ?? abort(404);
        }

        return $user->organizationMemberships()
            ->where('organization_id', $organizationId)
            ->firstOrFail();
    }

    public static function organizationId(User $actor): int
    {
        $organizationId = (int) $actor->current_organization_id;

        abort_unless($organizationId > 0 && $actor->organizationMemberships()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->exists(), 403, 'An active organization is required.');

        return $organizationId;
    }

    public static function displayRole(User $actor, User $user): string
    {
        if ($actor->isPlatformAdmin()) {
            return $user->role;
        }

        return self::membership($actor, $user)?->role ?? 'member';
    }

    public static function displayStatus(User $actor, User $user): string
    {
        if ($actor->isPlatformAdmin()) {
            return $user->status;
        }

        return self::membership($actor, $user)?->status ?? 'inactive';
    }

    public static function permissionForRole(string $role): string
    {
        return match ($role) {
            'coach' => 'coaches.manage',
            'athlete' => 'athletes.manage',
            default => 'users.manage',
        };
    }

    public static function authorizeRole(User $actor, string $role): void
    {
        abort_unless(
            $actor->hasPermission('users.manage') || $actor->hasPermission(self::permissionForRole($role)),
            403
        );
    }

    private static function withOperationalCounts(Builder $query, ?int $organizationId): Builder
    {
        $relations = [
            'coachAssignments as coach_assignments_count',
            'athleteAssignments as athlete_assignments_count',
            'coachPrograms as coach_programs_count',
            'progressEntries as progress_entries_count',
        ];

        if (! $organizationId) {
            return $query->withCount($relations);
        }

        return $query->withCount([
            'coachAssignments as coach_assignments_count' => fn (Builder $query) => $query->where('organization_id', $organizationId),
            'athleteAssignments as athlete_assignments_count' => fn (Builder $query) => $query->where('organization_id', $organizationId),
            'coachPrograms as coach_programs_count' => fn (Builder $query) => $query->where('organization_id', $organizationId),
            'progressEntries as progress_entries_count' => fn (Builder $query) => $query->where('organization_id', $organizationId),
        ]);
    }

    private static function isGlobalRole(string $role): bool
    {
        return in_array($role, ['owner', 'admin', 'coach', 'athlete'], true);
    }
}
