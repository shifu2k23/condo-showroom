<?php

use App\Models\User;
use App\Support\Tenancy\TenantManager;

test('tenant manager resolves tenant id from authenticated tenant admin when in-memory context is cleared', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $tenantManager = app(TenantManager::class);
    $tenantManager->clear();

    expect($tenantManager->currentId())->toBe($admin->tenant_id);
});

test('tenant manager does not resolve tenant id from authenticated super admin fallback', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $this->actingAs($superAdmin);

    $tenantManager = app(TenantManager::class);
    $tenantManager->clear();

    expect($tenantManager->currentId())->toBeNull();
});
