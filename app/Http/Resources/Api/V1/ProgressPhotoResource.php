<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgressPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'visibility' => $this->visibility,
            'taken_on' => $this->taken_on?->toDateString(),
            'notes' => $this->notes,
            'mime_type' => $this->mimeType(),
            'url' => route('api.v1.media.progress-photos', $this->resource),
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn () => $this->uploadedBy ? new CompactUserResource($this->uploadedBy) : null),
            'can_delete' => $this->uploaded_by === $request->user()?->id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function mimeType(): string
    {
        return match (strtolower(pathinfo((string) $this->path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
