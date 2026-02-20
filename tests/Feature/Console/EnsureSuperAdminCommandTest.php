<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('super-admin ensure command creates new super admin user', function () {
    $this->artisan('super-admin:ensure', [
        'email' => 'owner@example.com',
        '--name' => 'Owner',
        '--password' => 'Secret!12345',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'owner@example.com')->first();

    expect($user)->not->toBeNull();
    expect((bool) $user?->is_super_admin)->toBeTrue();
    expect((bool) $user?->is_admin)->toBeFalse();
    expect($user?->tenant_id)->toBeNull();
    expect($user?->email_verified_at)->not->toBeNull();
    expect(Hash::check('Secret!12345', (string) $user?->password))->toBeTrue();
});

test('super-admin ensure command upgrades existing tenant admin to super admin', function () {
    $tenant = Tenant::factory()->create();

    $user = User::factory()->admin()->create([
        'email' => 'owner@example.com',
        'tenant_id' => $tenant->id,
        'is_super_admin' => false,
    ]);

    $this->artisan('super-admin:ensure', [
        'email' => 'owner@example.com',
        '--password' => 'Secret!67890',
    ])->assertSuccessful();

    $user->refresh();

    expect((bool) $user->is_super_admin)->toBeTrue();
    expect((bool) $user->is_admin)->toBeFalse();
    expect($user->tenant_id)->toBeNull();
    expect(Hash::check('Secret!67890', (string) $user->password))->toBeTrue();
});
