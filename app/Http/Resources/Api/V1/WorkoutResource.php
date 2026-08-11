<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $log = $this->resource->relationLoaded('executionLog') ? $this->executionLog : null;
        $session = $this->resource->relationLoaded('session') ? $this->session : null;
        $assignment = $this->resource->relationLoaded('assignment') ? $this->assignment : null;

        return [
            'id' => $this->id,
            'scheduled_for' => $this->scheduled_for?->toIso8601String(),
            'status' => $this->status,
            'coach_notes' => $this->coach_notes,
            'athlete_notes' => $this->athlete_notes,
            'coach' => $this->resource->relationLoaded('coach') && $this->coach
                ? new CompactUserResource($this->coach)
                : null,
            'athlete' => $this->resource->relationLoaded('athlete') && $this->athlete
                ? new CompactUserResource($this->athlete)
                : null,
            'program' => $assignment && $assignment->relationLoaded('program') && $assignment->program ? [
                'id' => $assignment->program->id,
                'title' => $assignment->program->title,
                'goal' => $assignment->program->goal,
            ] : null,
            'session' => $session ? [
                'id' => $session->id,
                'title' => $session->title,
                'focus' => $session->focus,
                'estimated_minutes' => $session->estimated_minutes,
                'coach_notes' => $session->coach_notes,
                'media_url' => $session->media_url,
                'exercises' => $session->relationLoaded('prescribedExercises')
                    ? ExercisePrescriptionResource::collection($session->prescribedExercises)
                    : [],
            ] : null,
            'execution' => $log ? [
                'id' => $log->id,
                'status' => $log->status,
                'duration_minutes' => $log->duration_minutes,
                'rpe' => $log->rpe,
                'notes' => $log->notes,
                'completed_at' => $log->completed_at?->toIso8601String(),
                'sync_version' => $log->sync_version,
                'sets' => $log->relationLoaded('setLogs') ? $log->setLogs->map(fn ($set): array => [
                    'id' => $set->id,
                    'exercise_id' => $set->training_session_exercise_id,
                    'exercise_index' => $set->exercise_index,
                    'exercise_name' => $set->exercise_name,
                    'set_number' => $set->set_number,
                    'target_reps' => $set->target_reps,
                    'target_load' => $set->target_load,
                    'target_rest_seconds' => $set->target_rest_seconds,
                    'actual_reps' => $set->actual_reps,
                    'actual_load' => $set->actual_load,
                    'rpe' => $set->actual_rpe,
                    'notes' => $set->notes,
                    'completed' => $set->completed_at !== null,
                ])->values() : [],
            ] : null,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
