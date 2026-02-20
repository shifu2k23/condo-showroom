<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use App\Models\User;

class TenantManager
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function setCurrent(?Tenant $tenant): void
    {
        $this->currentTenant->set($tenant);
    }

    public function current(): ?Tenant
    {
        $tenant = $this->currentTenant->get();
        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        return $this->tenantFromAuthenticatedUser();
    }

    public function currentId(): ?int
    {
        $tenantId = $this->currentTenant->id();
        if ($tenantId !== null) {
            return $tenantId;
        }

        $user = $this->authenticatedTenantUser();
        if (! $user instanceof User) {
            return null;
        }

        return $this->tenantIdFromUser($user);
    }

    public function clear(): void
    {
        $this->currentTenant->clear();
    }

    public function shouldBypassTenantScope(): bool
    {
        if (app()->runningInConsole() && $this->currentId() === null) {
            return true;
        }

        if (! app()->bound('request')) {
            return false;
        }

        $request = request();

        if ((bool) $request->attributes->get('tenancy.disabled', false)) {
            return true;
        }

        return false;
    }

    private function tenantFromAuthenticatedUser(): ?Tenant
    {
        $user = $this->authenticatedTenantUser();
        if (! $user instanceof User) {
            return null;
        }

        if ($user->relationLoaded('tenant')) {
            $tenant = $user->getRelation('tenant');

            return $tenant instanceof Tenant ? $tenant : null;
        }

        $tenantId = $this->tenantIdFromUser($user);
        if ($tenantId === null) {
            return null;
        }

        return Tenant::query()->find($tenantId);
    }

    private function authenticatedTenantUser(): ?User
    {
        if (! app()->bound('auth')) {
            return null;
        }

        $user = auth()->user();
        if (! $user instanceof User || (bool) $user->is_super_admin) {
            return null;
        }

        return $user;
    }

    private function tenantIdFromUser(User $user): ?int
    {
        $tenantId = $user->tenant_id;
        if (! is_numeric($tenantId)) {
            return null;
        }

        $tenantId = (int) $tenantId;

        return $tenantId > 0 ? $tenantId : null;
    }
}
