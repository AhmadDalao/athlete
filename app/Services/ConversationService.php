<?php

namespace App\Services;

use App\Models\CoachAthleteAssignment;
use App\Models\Conversation;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @throws AuthorizationException */
    public function direct(User $actor, User $other): Conversation
    {
        $organizationId = app(OrganizationContext::class)->id() ?: $actor->current_organization_id;

        if (! $organizationId || ! $this->canMessage($actor, $other, $organizationId)) {
            throw new AuthorizationException('You cannot message this person in the active organization.');
        }

        $conversation = Conversation::query()
            ->where('organization_id', $organizationId)
            ->where('type', 'direct')
            ->whereHas('participants', fn ($query) => $query->whereKey($actor->id))
            ->whereHas('participants', fn ($query) => $query->whereKey($other->id))
            ->first();

        if ($conversation) {
            return $conversation;
        }

        return DB::transaction(function () use ($organizationId, $actor, $other): Conversation {
            $conversation = Conversation::create([
                'organization_id' => $organizationId,
                'type' => 'direct',
                'subject' => null,
            ]);
            $conversation->participants()->attach([
                $actor->id => ['role' => $this->participantRole($actor)],
                $other->id => ['role' => $this->participantRole($other)],
            ]);
            $this->audit->record('conversation.created', 'conversation', $conversation->id, "Started a direct conversation with {$other->name}.", $actor->id);

            return $conversation;
        });
    }

    public function canMessage(User $actor, User $other, int $organizationId): bool
    {
        $bothActive = $actor->organizationMemberships()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->exists()
            && $other->organizationMemberships()
                ->where('organization_id', $organizationId)
                ->where('status', 'active')
                ->exists();

        if (! $bothActive) {
            return false;
        }

        if ($actor->isCoach() && $other->isAthlete()) {
            return CoachAthleteAssignment::query()
                ->where('coach_id', $actor->id)
                ->where('athlete_id', $other->id)
                ->where('status', 'active')
                ->exists();
        }

        if ($actor->isAthlete() && $other->isCoach()) {
            return CoachAthleteAssignment::query()
                ->where('coach_id', $other->id)
                ->where('athlete_id', $actor->id)
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    private function participantRole(User $user): string
    {
        return $user->isCoach() ? 'coach' : 'athlete';
    }
}
