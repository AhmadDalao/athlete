<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkoutExecutionRequest;
use App\Http\Resources\Api\V1\WorkoutResource;
use App\Models\ScheduledWorkout;
use App\Queries\Athlete\AthleteWorkspaceQuery;
use App\Services\WorkoutExecutionService;
use Illuminate\Http\JsonResponse;

class WorkoutExecutionController extends Controller
{
    use RespondsWithApi;

    public function update(
        WorkoutExecutionRequest $request,
        ScheduledWorkout $workout,
        WorkoutExecutionService $execution,
    ): JsonResponse {
        abort_unless(AthleteWorkspaceQuery::canOpenWorkout($workout, $request->user()->id), 403);
        $validated = $request->validated();
        $existing = $workout->executionLog()->first();

        if (isset($validated['sync_version']) && $existing && (int) $validated['sync_version'] !== $existing->sync_version) {
            return response()->json([
                'data' => ['workout' => new WorkoutResource($workout->load(['executionLog.setLogs']))],
                'meta' => (object) [],
                'links' => (object) [],
                'error' => [
                    'code' => 'sync_conflict',
                    'message' => 'This workout changed on another device. Refresh before saving again.',
                ],
            ], 409);
        }

        $execution->save($workout, $request->user(), [
            'notes' => $validated['notes'] ?? null,
            'durationMinutes' => $validated['duration_minutes'] ?? null,
            'rpe' => $validated['rpe'] ?? null,
            'setLogs' => collect($validated['sets'] ?? [])->map(fn (array $set): array => [
                'exercise_id' => $set['exercise_id'] ?? null,
                'exercise_index' => $set['exercise_index'],
                'exercise' => $set['exercise_name'],
                'set' => $set['set_number'],
                'target_reps' => $set['target_reps'] ?? null,
                'target_load' => $set['target_load'] ?? null,
                'target_rest_seconds' => $set['target_rest_seconds'] ?? null,
                'actual_reps' => $set['actual_reps'] ?? null,
                'actual_load' => $set['actual_load'] ?? null,
                'rpe' => $set['rpe'] ?? null,
                'notes' => $set['notes'] ?? null,
                'completed' => $set['completed'],
            ])->all(),
        ], $validated['status']);

        return $this->success(new WorkoutResource($workout->fresh()->load([
            'coach',
            'athlete',
            'assignment.program',
            'session.prescribedExercises',
            'executionLog.setLogs',
        ])));
    }
}
