<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isTenantAdmin($user);
    }

    public function view(User $user, Unit $unit): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $unit->tenant_id);
    }

    public function create(User $user): bool
    {
        return $this->isTenantAdmin($user);
    }

    public function update(User $user, Unit $unit): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $unit->tenant_id);
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $unit->tenant_id);
    }

    public function restore(User $user, Unit $unit): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $unit->tenant_id);
    }

    public function forceDelete(User $user, Unit $unit): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $unit->tenant_id);
    }

    public function setAvailability(User $user, Unit $unit): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $unit->tenant_id);
    }

    public function manageImages(User $user, Unit $unit): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $unit->tenant_id);
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
