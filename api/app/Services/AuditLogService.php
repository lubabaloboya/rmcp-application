<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    public static function log(?int $userId, string $action, string $module, ?int $recordId = null): void
    {
        AuditLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
        ]);
    }
}
