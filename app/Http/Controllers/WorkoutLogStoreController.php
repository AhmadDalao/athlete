<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Enums\WorkoutCompletionStatus;
use App\Models\TrainingSession;
use App\Models\WorkoutLog;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkoutLogStoreController extends Controller
{
    public function __invoke(Request $request, TrainingSession $trainingSession, PlatformAuditLogger $auditLogger): RedirectResponse
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
            'energy_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'soreness_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'stress_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'sleep_quality_score' => ['nullable', 'integer', 'min:1', 'max:10'],
            'notes' => ['nullable', 'string'],
        ]);

        $log = WorkoutLog::query()->updateOrCreate(
            [
                'training_session_id' => $trainingSession->id,
                'athlete_id' => $user->id,
            ],
            [
                'completion_status' => $validated['completion_status'],
                'performed_at' => $validated['performed_at'] ?? now(),
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'exertion_rating' => $validated['exertion_rating'] ?? null,
                'energy_score' => $validated['energy_score'] ?? null,
                'soreness_score' => $validated['soreness_score'] ?? null,
                'stress_score' => $validated['stress_score'] ?? null,
                'sleep_quality_score' => $validated['sleep_quality_score'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ],
        );

        $auditLogger->record(
            $request,
            'workout_log.saved',
            $log,
            "{$user->name} logged {$trainingSession->title} as {$log->completion_status->value}.",
            [
                'training_session_id' => $trainingSession->id,
                'completion_status' => $log->completion_status->value,
                'duration_minutes' => $log->duration_minutes,
                'exertion_rating' => $log->exertion_rating,
            ],
        );

        return redirect()->route('training.index');
    }
}
