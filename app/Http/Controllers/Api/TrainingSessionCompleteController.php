<?php

namespace App\Http\Controllers\Api;

use App\Enums\WorkoutCompletionStatus;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\AthleteWorkoutExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainingSessionCompleteController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(Request $request, TrainingSession $trainingSession, AthleteWorkoutExecutionService $workouts): JsonResponse
    {
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

        $workouts->complete($athlete, $trainingSession, WorkoutCompletionStatus::from($validated['completion_status']), $validated);

        return response()->json([
            'data' => $workouts->payload($athlete, $trainingSession),
            'meta' => $this->metaPayload(['message' => 'Workout status saved.']),
        ]);
    }
}
