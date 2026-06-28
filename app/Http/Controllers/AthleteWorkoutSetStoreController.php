<?php

namespace App\Http\Controllers;

use App\Models\TrainingSession;
use App\Models\User;
use App\Services\AthleteWorkoutExecutionService;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AthleteWorkoutSetStoreController extends Controller
{
    public function __invoke(
        Request $request,
        TrainingSession $trainingSession,
        AthleteWorkoutExecutionService $workouts,
        PlatformAuditLogger $auditLogger,
    ): RedirectResponse {
        /** @var User $athlete */
        $athlete = $request->user();

        $validated = $request->validate([
            'sets' => ['required', 'array'],
            'sets.*.exercise_index' => ['required', 'integer', 'min:0'],
            'sets.*.set_number' => ['required', 'integer', 'min:1'],
            'sets.*.actual_reps' => ['nullable', 'string', 'max:80'],
            'sets.*.actual_load' => ['nullable', 'string', 'max:80'],
            'sets.*.actual_rpe' => ['nullable', 'integer', 'min:1', 'max:10'],
            'sets.*.completed' => ['boolean'],
            'sets.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $log = $workouts->saveSetLogs($athlete, $trainingSession, $validated['sets']);

        $auditLogger->record(
            $request,
            'workout_sets.saved',
            $log,
            "{$athlete->name} saved set-level execution for {$trainingSession->title}.",
            [
                'training_session_id' => $trainingSession->id,
                'set_count' => count($validated['sets']),
                'completion_status' => $log->completion_status->value,
            ],
        );

        return back()->with('success', 'Workout sets saved.');
    }
}
