<?php

use App\Models\AppSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantManager;
use Livewire\Livewire;

test('app settings are isolated per tenant and can share the same key', function () {
    $tenantA = Tenant::factory()->create(['slug' => 'tenant-a']);
    $tenantB = Tenant::factory()->create(['slug' => 'tenant-b']);
    $tenantManager = app(TenantManager::class);

    $tenantManager->setCurrent($tenantA);
    AppSetting::put('site_name', 'Tenant A Brand');

    $tenantManager->setCurrent($tenantB);
    AppSetting::put('site_name', 'Tenant B Brand');

    $this->assertDatabaseHas('app_settings', [
        'tenant_id' => $tenantA->id,
        'key' => 'site_name',
        'value' => 'Tenant A Brand',
    ]);

    $this->assertDatabaseHas('app_settings', [
        'tenant_id' => $tenantB->id,
        'key' => 'site_name',
        'value' => 'Tenant B Brand',
    ]);

    expect(
        AppSetting::query()
            ->withoutGlobalScope('tenant')
            ->where('key', 'site_name')
            ->count()
    )->toBe(2);

    $tenantManager->setCurrent($tenantA);
    expect(AppSetting::get('site_name'))->toBe('Tenant A Brand');

    $tenantManager->setCurrent($tenantB);
    expect(AppSetting::get('site_name'))->toBe('Tenant B Brand');
});

test('tenant branding and showroom appearance updates apply only to that tenant', function () {
    $tenantA = Tenant::factory()->create(['slug' => 'skybear-hotels']);
    $tenantB = Tenant::factory()->create(['slug' => 'bayside-suites']);

    $adminA = User::factory()->admin()->create(['tenant_id' => $tenantA->id]);
    $adminB = User::factory()->admin()->create(['tenant_id' => $tenantB->id]);

    $tenantManager = app(TenantManager::class);

    $tenantManager->setCurrent($tenantA);
    Livewire::actingAs($adminA)
        ->test('pages::settings.profile')
        ->set('websiteName', 'Skybear Hotels')
        ->set('showroomAppearance', 'dark')
        ->call('updateWebsiteBranding')
        ->assertHasNoErrors();

    $tenantManager->setCurrent($tenantB);
    Livewire::actingAs($adminB)
        ->test('pages::settings.profile')
        ->set('websiteName', 'Bayside Suites')
        ->set('showroomAppearance', 'light')
        ->call('updateWebsiteBranding')
        ->assertHasNoErrors();

    $tenantManager->clear();

    $this->assertDatabaseHas('app_settings', [
        'tenant_id' => $tenantA->id,
        'key' => 'site_name',
        'value' => 'Skybear Hotels',
    ]);

    $this->assertDatabaseHas('app_settings', [
        'tenant_id' => $tenantA->id,
        'key' => 'showroom_appearance',
        'value' => 'dark',
    ]);

    $this->assertDatabaseHas('app_settings', [
        'tenant_id' => $tenantB->id,
        'key' => 'site_name',
        'value' => 'Bayside Suites',
    ]);

    $this->assertDatabaseHas('app_settings', [
        'tenant_id' => $tenantB->id,
        'key' => 'showroom_appearance',
        'value' => 'light',
    ]);

    $this->get(route('home', ['tenant' => $tenantA->slug]))
        ->assertOk()
        ->assertSee('Skybear Hotels')
        ->assertSee('showroom-theme-dark', false)
        ->assertDontSee('Bayside Suites');

    $this->get(route('home', ['tenant' => $tenantB->slug]))
        ->assertOk()
        ->assertSee('Bayside Suites')
        ->assertSee('showroom-theme-light', false)
        ->assertDontSee('Skybear Hotels');
});

