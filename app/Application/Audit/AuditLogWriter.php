<?php

namespace App\Application\Audit;

use App\Models\AuditLog;

final class AuditLogWriter
{
    public function write(
        string $action,
        ?int $unitId,
        ?int $userId,
        array $changes = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AuditLog {
        // Intended to be called inside an outer DB::transaction()
        // so state mutation + audit entry commit atomically.
        return AuditLog::query()->create([
            'action' => $action,
            'unit_id' => $unitId,
            'user_id' => $userId,
            'changes' => $changes ?: null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
