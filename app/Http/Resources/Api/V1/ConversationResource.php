<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'subject' => $this->subject,
            'participants' => $this->resource->relationLoaded('participants')
                ? CompactUserResource::collection($this->participants)
                : [],
            'latest_message' => $this->relationLoaded('latestMessage') && $this->latestMessage
                ? new MessageResource($this->latestMessage)
                : null,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
