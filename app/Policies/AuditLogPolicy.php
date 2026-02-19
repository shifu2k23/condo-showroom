<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin && ! $user->is_super_admin;
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return (bool) $user->is_admin && ! $user->is_super_admin;
    }
}
