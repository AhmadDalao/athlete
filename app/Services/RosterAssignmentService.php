<?php

namespace App\Services;

use App\Models\CoachAthleteAssignment;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RosterAssignmentService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function assign(User $actor, int $coachId, int $athleteId): CoachAthleteAssignment
    {
        $organizationId = $actor->current_organization_id;
        if (! $organizationId) {
            throw ValidationException::withMessages(['coachId' => 'Select an organization before managing its roster.']);
        }

        $this->assertMembership($organizationId, $coachId, 'coach', 'coachId');
        $this->assertMembership($organizationId, $athleteId, 'athlete', 'athleteId');

        return DB::transaction(function () use ($organizationId, $coachId, $athleteId): CoachAthleteAssignment {
            $assignment = CoachAthleteAssignment::query()->updateOrCreate(
                [
                    'organization_id' => $organizationId,
                    'coach_id' => $coachId,
                    'athlete_id' => $athleteId,
                ],
                ['status' => 'active', 'started_at' => today(), 'ended_at' => null],
            );
            $assignment->load(['coach', 'athlete']);
            $this->audit->record(
                'roster.assigned',
                'coach_athlete_assignment',
                $assignment->id,
                "Assigned coach {$assignment->coach->name} to athlete {$assignment->athlete->name}.",
            );

            return $assignment;
        });
    }

    public function end(User $actor, CoachAthleteAssignment $assignment): void
    {
        abort_unless($assignment->organization_id === $actor->current_organization_id, 403);
        $assignment->loadMissing(['coach', 'athlete']);
        $assignment->update(['status' => 'inactive', 'ended_at' => today()]);
        $this->audit->record(
            'roster.ended',
            'coach_athlete_assignment',
            $assignment->id,
            "Ended the roster link between {$assignment->coach->name} and {$assignment->athlete->name}.",
        );
    }

    private function assertMembership(int $organizationId, int $userId, string $role, string $field): void
    {
        $exists = OrganizationMembership::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->where('role', $role)
            ->where('status', 'active')
            ->exists();
        if (! $exists) {
            throw ValidationException::withMessages([$field => "Choose an active {$role} in this organization."]);
        }
    }
}
