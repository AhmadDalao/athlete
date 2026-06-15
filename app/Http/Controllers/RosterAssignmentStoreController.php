<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\RosterAssignmentStoreRequest;
use App\Models\CoachAthleteAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class RosterAssignmentStoreController extends Controller
{
    public function __invoke(RosterAssignmentStoreRequest $request): RedirectResponse
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

        return to_route('roster.index');
    }
}
