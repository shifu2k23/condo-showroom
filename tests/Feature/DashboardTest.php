<?php

use App\Models\User;
use App\Models\Tenant;

test('guests are redirected to the login page for admin dashboard', function () {
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('login'));
});

test('non-admin authenticated users cannot access admin dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));
    $response->assertForbidden();
});

test('admin authenticated users can visit the dashboard', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));
    $response->assertOk();
});

test('dashboard add unit control remains direct link to create form', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Add Unit');
    $response->assertDontSee('Davao Condo Presets');
});

test('super admin hitting /dashboard is redirected to tenant management', function () {
    $user = User::factory()->superAdmin()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('super.tenants.index'));
});

test('super admin can access super panel even if tenant_id is set', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'is_admin' => false,
        'is_super_admin' => true,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('super.tenants.index'));

    $response->assertOk();
});
