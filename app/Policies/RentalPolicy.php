<?php

namespace App\Policies;

use App\Models\Rental;
use App\Models\User;

class RentalPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isTenantAdmin($user);
    }

    public function view(User $user, Rental $rental): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $rental->tenant_id);
    }

    public function create(User $user): bool
    {
        return $this->isTenantAdmin($user);
    }

    public function update(User $user, Rental $rental): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $rental->tenant_id);
    }

    public function delete(User $user, Rental $rental): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $rental->tenant_id);
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
