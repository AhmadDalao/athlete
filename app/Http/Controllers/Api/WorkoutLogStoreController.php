<?php

namespace App\Http\Controllers\Api;

use App\Enums\RoleName;
use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiWorkoutLogStoreRequest;
use App\Models\TrainingSession;
use App\Models\WorkoutLog;
use Illuminate\Http\JsonResponse;

class WorkoutLogStoreController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(ApiWorkoutLogStoreRequest $request, TrainingSession $trainingSession): JsonResponse
    {
        $user = $request->user();
        $trainingSession->loadMissing('program');

        abort_unless(
            $user
                && $user->hasRole(RoleName::Athlete)
                && $trainingSession->program->athlete_id === $user->id,
            403
        );

        $workoutLog = WorkoutLog::query()->updateOrCreate(
            [
                'training_session_id' => $trainingSession->id,
                'athlete_id' => $user->id,
            ],
            [
                'completion_status' => $request->validated('completion_status'),
                'performed_at' => $request->validated('performed_at') ?? now(),
                'duration_minutes' => $request->validated('duration_minutes'),
                'exertion_rating' => $request->validated('exertion_rating'),
                'notes' => $request->validated('notes'),
            ],
        );

        return response()->json([
            'data' => $this->workoutLogPayload($workoutLog->fresh()),
            'meta' => $this->metaPayload([
                'trainingSessionId' => $trainingSession->id,
            ]),
        ]);
    }
}
