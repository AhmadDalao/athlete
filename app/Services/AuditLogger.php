<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function record(
        string $action,
        string $entity,
        ?int $entityId,
        string $summary,
        ?int $userId = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'summary' => $summary,
            'ip_address' => request()->ip(),
        ]);
    }
}
