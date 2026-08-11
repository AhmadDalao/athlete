<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgressEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'logged_on' => $this->logged_on?->toDateString(),
            'weight_kg' => $this->weight !== null ? (float) $this->weight : null,
            'calories_kcal' => $this->calories,
            'protein_g' => $this->protein,
            'hydration_ml' => $this->hydration,
            'sleep_quality' => $this->sleep_quality,
            'soreness' => $this->soreness,
            'energy' => $this->energy,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
