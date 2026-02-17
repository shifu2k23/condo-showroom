<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Unit;
use App\Models\User;
use App\Support\Security\AuditLogSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function __construct(
        private readonly AuditLogSanitizer $sanitizer
    ) {}

    public function log(
        string $action,
        ?Unit $unit = null,
        ?array $changes = null,
        ?User $user = null,
        ?Request $request = null
    ): AuditLog {
        $request ??= request();
        $user ??= Auth::user();

        return AuditLog::create([
            'unit_id' => $unit?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'changes' => $this->sanitizer->sanitize($changes),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
