<?php

namespace App\Http\Controllers;

use App\Enums\WorkoutCompletionStatus;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\AthleteWorkoutExecutionService;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AthleteWorkoutCompleteController extends Controller
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
            'completion_status' => ['required', Rule::enum(WorkoutCompletionStatus::class)],
            'performed_at' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'exertion_rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'energy_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'soreness_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'stress_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'sleep_quality_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string'],
        ]);

        $status = WorkoutCompletionStatus::from($validated['completion_status']);
        $log = $workouts->complete($athlete, $trainingSession, $status, $validated);

        $auditLogger->record(
            $request,
            'workout_log.completed',
            $log,
            "{$athlete->name} marked {$trainingSession->title} as {$log->completion_status->value}.",
            [
                'training_session_id' => $trainingSession->id,
                'completion_status' => $log->completion_status->value,
            ],
        );

        return back()->with('success', 'Workout status saved.');
    }
}
