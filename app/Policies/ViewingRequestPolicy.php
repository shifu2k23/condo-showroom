<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ViewingRequest;

class ViewingRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isTenantAdmin($user);
    }

    public function view(User $user, ViewingRequest $viewingRequest): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $viewingRequest->tenant_id);
    }

    public function confirm(User $user, ViewingRequest $viewingRequest): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $viewingRequest->tenant_id);
    }

    public function cancel(User $user, ViewingRequest $viewingRequest): bool
    {
        return $this->isTenantAdmin($user) && $this->ownsResource($user, $viewingRequest->tenant_id);
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
