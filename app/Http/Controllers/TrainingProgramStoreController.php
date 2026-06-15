<?php

namespace App\Http\Controllers;

use App\Enums\CoachAthleteStatus;
use App\Enums\RoleName;
use App\Enums\TrainingProgramStatus;
use App\Models\TrainingProgram;
use App\Support\TrainingExerciseParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainingProgramStoreController extends Controller
{
    public function __invoke(Request $request, TrainingExerciseParser $exerciseParser): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->hasRole(RoleName::Coach), 403);

        $validated = $request->validate([
            'athlete_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'goal' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
            'first_session_title' => ['required', 'string', 'max:255'],
            'first_session_date' => ['required', 'date'],
            'first_session_focus' => ['nullable', 'string', 'max:255'],
            'first_session_instructions' => ['nullable', 'string'],
            'first_session_exercises' => ['nullable', 'string'],
        ]);

        $hasRosterAssignment = $user->coachAssignments()
            ->where('status', CoachAthleteStatus::Active->value)
            ->where('athlete_id', $validated['athlete_id'])
            ->exists();

        abort_unless($hasRosterAssignment, 403);

        $program = TrainingProgram::query()->create([
            'coach_id' => $user->id,
            'athlete_id' => $validated['athlete_id'],
            'title' => $validated['title'],
            'goal' => $validated['goal'],
            'status' => TrainingProgramStatus::Active,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'notes' => $validated['notes'],
        ]);

        $program->sessions()->create([
            'title' => $validated['first_session_title'],
            'scheduled_date' => $validated['first_session_date'],
            'focus' => $validated['first_session_focus'],
            'instructions' => $validated['first_session_instructions'],
            'exercises' => $exerciseParser->parse($validated['first_session_exercises'] ?? null),
            'sort_order' => 1,
        ]);

        return redirect()->route('training.index');
    }
}
