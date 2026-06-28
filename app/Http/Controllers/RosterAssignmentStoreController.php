<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\RosterAssignmentStoreRequest;
use App\Models\CoachAthleteAssignment;
use App\Models\User;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\RedirectResponse;

class RosterAssignmentStoreController extends Controller
{
    public function __invoke(RosterAssignmentStoreRequest $request, PlatformAuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user()?->loadMissing('roles');

        abort_unless($viewer && ($viewer->hasRole(RoleName::Admin) || $viewer->hasRole(RoleName::Coach)), 403);

        $validated = $request->validated();
        $coachId = $viewer->hasRole(RoleName::Admin)
            ? (int) $validated['coach_id']
            : $viewer->id;

        $assignment = CoachAthleteAssignment::query()->firstOrNew([
            'coach_id' => $coachId,
            'athlete_id' => (int) $validated['athlete_id'],
        ]);

        $assignment->fill([
            'status' => $validated['status'],
            'goal' => $validated['goal'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'started_at' => $validated['started_at'] ?? $assignment->started_at?->toDateString(),
            'ended_at' => $validated['ended_at'] ?? null,
        ]);
        $assignment->syncLifecycleDates();
        $assignment->save();

        $assignment->loadMissing(['coach', 'athlete']);

        $auditLogger->record(
            $request,
            $assignment->wasRecentlyCreated ? 'roster_assignment.created' : 'roster_assignment.updated',
            $assignment,
            "{$assignment->coach->name} assigned to {$assignment->athlete->name} ({$assignment->status->value}).",
            [
                'coach_id' => $assignment->coach_id,
                'athlete_id' => $assignment->athlete_id,
                'status' => $assignment->status->value,
            ],
        );

        return to_route('roster.index');
    }
}
