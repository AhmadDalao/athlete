<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Http\Requests\RosterAssignmentUpdateRequest;
use App\Models\CoachAthleteAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class RosterAssignmentUpdateController extends Controller
{
    public function __invoke(RosterAssignmentUpdateRequest $request, CoachAthleteAssignment $assignment): RedirectResponse
    {
        $viewer = $this->manageableViewer($request->user(), $assignment);

        $validated = $request->validated();

        $assignment->fill([
            'status' => $validated['status'],
            'goal' => $validated['goal'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'started_at' => $validated['started_at'] ?? null,
            'ended_at' => $validated['ended_at'] ?? null,
        ]);
        $assignment->syncLifecycleDates();
        $assignment->save();

        return to_route('roster.index');
    }

    private function manageableViewer(?User $viewer, CoachAthleteAssignment $assignment): User
    {
        abort_unless($viewer, 403);

        $viewer->loadMissing('roles');

        abort_unless($viewer->hasRole(RoleName::Admin) || $viewer->hasRole(RoleName::Coach), 403);

        if ($viewer->hasRole(RoleName::Admin)) {
            return $viewer;
        }

        abort_unless($assignment->coach_id === $viewer->id, 404);

        return $viewer;
    }
}
