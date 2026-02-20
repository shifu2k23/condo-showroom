<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isTenantAdmin($user);
    }

    public function view(User $user, Category $category): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $category->tenant_id);
    }

    public function create(User $user): bool
    {
        return $this->isTenantAdmin($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $category->tenant_id);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $category->tenant_id);
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
