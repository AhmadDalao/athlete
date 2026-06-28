<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\AthleteWorkoutExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingSessionSetStoreController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(Request $request, TrainingSession $trainingSession, AthleteWorkoutExecutionService $workouts): JsonResponse
    {
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

        $workouts->saveSetLogs($athlete, $trainingSession, $validated['sets']);

        return response()->json([
            'data' => $workouts->payload($athlete, $trainingSession),
            'meta' => $this->metaPayload(['message' => 'Workout sets saved.']),
        ]);
    }
}
