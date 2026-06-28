<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\FormatsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\AthleteWorkoutExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingSessionExecutionController extends Controller
{
    use FormatsApiPayloads;

    public function __invoke(Request $request, TrainingSession $trainingSession, AthleteWorkoutExecutionService $workouts): JsonResponse
    {
        /** @var User $athlete */
        $athlete = $request->user();

        return response()->json([
            'data' => $workouts->payload($athlete, $trainingSession),
            'meta' => $this->metaPayload(),
        ]);
    }
}
