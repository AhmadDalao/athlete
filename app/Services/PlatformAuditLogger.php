<?php

namespace App\Services;

use App\Models\PlatformAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Throwable;

class PlatformAuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(Request $request, string $action, ?Model $entity, string $summary, array $metadata = []): void
    {
        try {
            $actor = $request->user();

            PlatformAuditLog::query()->create([
                'actor_user_id' => $actor instanceof User ? $actor->id : null,
                'action' => $action,
                'entity_type' => $entity ? class_basename($entity) : null,
                'entity_id' => $entity?->getKey(),
                'summary' => $summary,
                'metadata' => $metadata === [] ? null : $metadata,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (Throwable) {
            // Audit logging should never make a user-facing workflow fail.
        }
    }
}
