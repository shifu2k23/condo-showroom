<?php

namespace Tests;

use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->bootstrapTenantContext();
    }

    protected function tearDown(): void
    {
        URL::defaults([]);
        app(TenantManager::class)->clear();

        parent::tearDown();
    }

    protected function bootstrapTenantContext(): void
    {
        try {
            if (! Schema::hasTable('tenants')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'default'],
            ['name' => 'Default Tenant', 'is_disabled' => false],
        );

        URL::defaults([
            'tenant' => $tenant->slug,
            'tenant:slug' => $tenant->slug,
        ]);
        app(TenantManager::class)->setCurrent($tenant);
    }
}
