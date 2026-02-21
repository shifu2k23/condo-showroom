<?php

use App\Livewire\Super\Tenants\Index as SuperTenantsIndex;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('super admin can create edit delete tenant account and change account password', function () {
    $tenant = Tenant::factory()->create(['name' => 'Atlas Residences']);
    $superAdmin = User::factory()->superAdmin()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($superAdmin)
        ->test(SuperTenantsIndex::class)
        ->set('accountTenantId', $tenant->id)
        ->set('accountName', 'Atlas Manager')
        ->set('accountEmail', 'atlas-manager@example.com')
        ->set('accountIsAdmin', true)
        ->set('accountPassword', 'InitialPass123!')
        ->set('accountPasswordConfirmation', 'InitialPass123!')
        ->call('saveAccount')
        ->assertHasNoErrors();

    $account = User::query()->where('email', 'atlas-manager@example.com')->firstOrFail();

    expect((int) $account->tenant_id)->toBe((int) $tenant->id);
    expect((bool) $account->is_super_admin)->toBeFalse();
    expect((bool) $account->is_admin)->toBeTrue();

    Livewire::actingAs($superAdmin)
        ->test(SuperTenantsIndex::class)
        ->call('editAccount', $account->id)
        ->set('accountName', 'Atlas Updated Manager')
        ->set('accountEmail', 'atlas-updated-manager@example.com')
        ->set('accountIsAdmin', false)
        ->call('saveAccount')
        ->assertHasNoErrors();

    $account->refresh();

    expect($account->name)->toBe('Atlas Updated Manager');
    expect($account->email)->toBe('atlas-updated-manager@example.com');
    expect((bool) $account->is_admin)->toBeFalse();

    Livewire::actingAs($superAdmin)
        ->test(SuperTenantsIndex::class)
        ->call('startPasswordChange', $account->id)
        ->set('newPassword', 'NewSecurePass123!')
        ->set('newPasswordConfirmation', 'NewSecurePass123!')
        ->call('updateAccountPassword')
        ->assertHasNoErrors();

    expect(Hash::check('NewSecurePass123!', (string) $account->fresh()->password))->toBeTrue();

    Livewire::actingAs($superAdmin)
        ->test(SuperTenantsIndex::class)
        ->call('deleteAccount', $account->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('users', [
        'id' => $account->id,
    ]);
});

test('super account management excludes super admin users and blocks direct edits to them', function () {
    $tenant = Tenant::factory()->create();
    $primarySuperAdmin = User::factory()->superAdmin()->create([
        'email_verified_at' => now(),
        'email' => 'primary-super@example.com',
    ]);
    $otherSuperAdmin = User::factory()->superAdmin()->create([
        'email_verified_at' => now(),
        'email' => 'other-super@example.com',
    ]);

    User::factory()->admin()->create([
        'tenant_id' => $tenant->id,
        'email' => 'tenant-admin@example.com',
        'is_super_admin' => false,
    ]);

    Livewire::actingAs($primarySuperAdmin)
        ->test(SuperTenantsIndex::class)
        ->assertSee('tenant-admin@example.com')
        ->assertDontSee('other-super@example.com');

    expect(fn () => Livewire::actingAs($primarySuperAdmin)
        ->test(SuperTenantsIndex::class)
        ->call('editAccount', $otherSuperAdmin->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($primarySuperAdmin)
        ->test(SuperTenantsIndex::class)
        ->call('startPasswordChange', $otherSuperAdmin->id))
        ->toThrow(ModelNotFoundException::class);
});
