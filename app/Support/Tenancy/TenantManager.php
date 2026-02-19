<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;

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
        return $this->currentTenant->get();
    }

    public function currentId(): ?int
    {
        return $this->currentTenant->id();
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

        return $request->is('super') || $request->is('super/*') || $request->routeIs('super.*');
    }
}
