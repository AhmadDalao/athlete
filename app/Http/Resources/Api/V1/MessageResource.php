<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender' => $this->resource->relationLoaded('sender') && $this->sender
                ? new CompactUserResource($this->sender)
                : null,
            'body' => $this->body,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'edited_at' => $this->edited_at?->toIso8601String(),
            'attachments' => $this->resource->relationLoaded('attachments') ? $this->attachments->map(fn ($attachment): array => [
                'id' => $attachment->id,
                'type' => $attachment->type,
                'name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'url' => route('messages.attachments', $attachment),
            ])->values() : [],
        ];
    }
}
