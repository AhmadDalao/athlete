<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $program = $this->resource->relationLoaded('program') ? $this->program : null;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'timezone' => $this->timezone,
            'notes' => $this->notes,
            'published_at' => $this->published_at?->toIso8601String(),
            'completion' => $this->completionStats(),
            'can_edit' => $program?->coach_id === $request->user()?->id,
            'athlete' => $this->resource->relationLoaded('athlete') && $this->athlete
                ? new CompactUserResource($this->athlete)
                : null,
            'program' => $program ? [
                'id' => $program->id,
                'kind' => $program->is_template ? 'preset' : 'athlete_plan',
                'source_program_id' => $program->source_program_id,
                'title' => $program->title,
                'goal' => $program->goal,
                'status' => $program->status,
                'estimated_weeks' => $program->estimated_weeks,
                'coach' => $program->relationLoaded('coach') ? new CompactUserResource($program->coach) : null,
                'phases' => $program->relationLoaded('phases') ? $program->phases->map(fn ($phase): array => [
                    'id' => $phase->id,
                    'title' => $phase->title,
                    'description' => $phase->description,
                    'order' => $phase->sort_order,
                    'duration_weeks' => $phase->duration_weeks,
                ])->values() : [],
            ] : null,
            'workouts' => $this->resource->relationLoaded('scheduledWorkouts')
                ? WorkoutResource::collection($this->scheduledWorkouts)
                : [],
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
