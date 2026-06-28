<?php

namespace App\Http\Controllers;

use App\Enums\CoachAthleteStatus;
use App\Models\CoachAthleteAssignment;
use App\Models\CoachAthleteMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageStoreController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $viewer */
        $viewer = $request->user();

        $validated = $request->validate([
            'assignment_id' => ['required', 'integer', 'exists:coach_athlete_assignments,id'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $assignment = CoachAthleteAssignment::query()->findOrFail($validated['assignment_id']);
        $isCoach = $assignment->coach_id === $viewer->id;
        $isAthlete = $assignment->athlete_id === $viewer->id;

        abort_unless($assignment->status === CoachAthleteStatus::Active && ($isCoach || $isAthlete), 403);

        CoachAthleteMessage::query()->create([
            'coach_athlete_assignment_id' => $assignment->id,
            'sender_id' => $viewer->id,
            'recipient_id' => $isCoach ? $assignment->athlete_id : $assignment->coach_id,
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Message sent.');
    }
}
