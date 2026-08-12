<?php

namespace App\Queries\Admin;

use App\Models\AthleteInvitation;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;

final class ManagedInvitationQuery
{
    public static function visibleTo(User $actor): Builder
    {
        $organizationId = app(OrganizationContext::class)->id();

        if (! $actor->isPlatformAdmin()) {
            $organizationId = ManagedUserQuery::organizationId($actor);
        }

        return AthleteInvitation::query()
            ->withoutGlobalScope('organization')
            ->when($organizationId, fn (Builder $query) => $query->where('organization_id', $organizationId));
    }

    public static function findVisibleOrFail(User $actor, int $invitationId): AthleteInvitation
    {
        return self::visibleTo($actor)->findOrFail($invitationId);
    }
}
