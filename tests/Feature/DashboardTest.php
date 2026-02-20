<?php

use App\Models\User;

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
