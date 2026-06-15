<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Enums\WorkoutCompletionStatus;
use App\Models\TrainingSession;
use App\Models\WorkoutLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkoutLogStoreController extends Controller
{
    public function __invoke(Request $request, TrainingSession $trainingSession): RedirectResponse
    {
        $user = $request->user();
        $trainingSession->loadMissing('program');

        abort_unless(
            $user
                && $user->hasRole(RoleName::Athlete)
                && $trainingSession->program->athlete_id === $user->id,
            403
        );

        $validated = $request->validate([
            'completion_status' => ['required', Rule::enum(WorkoutCompletionStatus::class)],
            'performed_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'exertion_rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string'],
        ]);

        WorkoutLog::query()->updateOrCreate(
            [
                'training_session_id' => $trainingSession->id,
                'athlete_id' => $user->id,
            ],
            [
                'completion_status' => $validated['completion_status'],
                'performed_at' => $validated['performed_at'] ?? now(),
                'duration_minutes' => $validated['duration_minutes'],
                'exertion_rating' => $validated['exertion_rating'],
                'notes' => $validated['notes'],
            ],
        );

        return redirect()->route('training.index');
    }
}
