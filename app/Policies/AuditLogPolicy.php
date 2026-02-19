<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isTenantAdmin($user);
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $auditLog->tenant_id);
    }

    private function isTenantAdmin(User $user): bool
    {
        return (bool) $user->is_admin && ! $user->is_super_admin && $user->tenant_id !== null;
    }

    private function ownsResource(User $user, ?int $tenantId): bool
    {
        return $tenantId !== null && (int) $user->tenant_id === (int) $tenantId;
    }
}
