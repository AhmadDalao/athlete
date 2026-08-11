<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExercisePrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order' => $this->sort_order,
            'section' => $this->section,
            'superset_label' => $this->superset_label,
            'name' => $this->name,
            'sets' => $this->target_sets,
            'reps' => $this->target_reps,
            'load' => $this->target_load,
            'unit' => $this->unit,
            'rest_seconds' => $this->rest_seconds,
            'notes' => $this->notes,
            'media_url' => $this->media_url,
            'movement_type' => $this->movement_type,
        ];
    }
}
