<?php

use App\Livewire\Admin\Units\Form as UnitForm;
use App\Livewire\Admin\Units\Index as UnitsIndex;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\Tenancy\TenantManager;
use Livewire\Livewire;

test('admin can create and update units', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();

    $this->actingAs($admin);

    Livewire::test(UnitForm::class)
        ->set('name', 'Sunrise Condo')
        ->set('category_id', (string) $category->id)
        ->set('location', 'Makati')
        ->set('latitude', '14.5547000')
        ->set('longitude', '121.0244000')
        ->set('address_text', 'Makati City, Metro Manila')
        ->set('description', 'City-facing condo unit')
        ->set('status', Unit::STATUS_AVAILABLE)
        ->set('nightly_price_php', 2500)
        ->set('monthly_price_php', 45000)
        ->set('price_display_mode', Unit::DISPLAY_NIGHT)
        ->set('estimator_mode', Unit::ESTIMATOR_HYBRID)
        ->set('allow_estimator', true)
        ->call('save')
        ->assertHasNoErrors();

    $unit = Unit::query()->where('name', 'Sunrise Condo')->firstOrFail();
    expect($unit->created_by)->toBe($admin->id);

    Livewire::test(UnitForm::class, ['unit' => $unit])
        ->set('name', 'Sunrise Condo Updated')
        ->set('latitude', '14.5678000')
        ->set('longitude', '121.0001000')
        ->set('price_display_mode', Unit::DISPLAY_MONTH)
        ->set('monthly_price_php', 50000)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('units', [
        'id' => $unit->id,
        'name' => 'Sunrise Condo Updated',
        'price_display_mode' => Unit::DISPLAY_MONTH,
    ]);
});

test('admin can set status, soft delete, and restore units with audit entries', function () {
    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create(['status' => Unit::STATUS_AVAILABLE, 'created_by' => $admin->id]);

    $this->actingAs($admin);

    Livewire::test(UnitsIndex::class)
        ->call('setUnavailable', $unit->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('units', ['id' => $unit->id, 'status' => Unit::STATUS_UNAVAILABLE]);
    $this->assertDatabaseHas('audit_logs', ['unit_id' => $unit->id, 'action' => 'SET_UNAVAILABLE']);

    Livewire::test(UnitsIndex::class)
        ->call('setAvailable', $unit->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('units', ['id' => $unit->id, 'status' => Unit::STATUS_AVAILABLE]);
    $this->assertDatabaseHas('audit_logs', ['unit_id' => $unit->id, 'action' => 'SET_AVAILABLE']);

    Livewire::test(UnitsIndex::class)
        ->call('deleteUnit', $unit->id)
        ->assertHasNoErrors();

    $this->assertSoftDeleted('units', ['id' => $unit->id]);

    Livewire::test(UnitsIndex::class)
        ->set('showTrashed', true)
        ->call('restoreUnit', $unit->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('units', ['id' => $unit->id, 'deleted_at' => null]);
});

test('admin unit create form applies davao condo preset and updates coordinates', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $response = $this->get(route('admin.units.create'));
    $response->assertOk();
    $response->assertSee('Condo Location Preset (Davao City)');

    Livewire::test(UnitForm::class)
        ->set('selectedLocationPreset', 'avida_towers_abreeza')
        ->assertSet('name', 'Avida Towers Abreeza')
        ->assertSet('location', 'Davao City')
        ->assertSet('address_text', 'Avida Towers Abreeza, Davao City')
        ->assertSet('latitude', '7.0908805')
        ->assertSet('longitude', '125.6097848')
        ->assertDispatched('leaflet-picker-set-coordinates');
});

test('unit create form auto-seeds default categories when tenant has none', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Category::query()->delete();

    $this->assertDatabaseMissing('categories', [
        'tenant_id' => $admin->tenant_id,
        'name' => '1 Bedroom',
    ]);

    $this->get(route('admin.units.create'))
        ->assertOk()
        ->assertSee('1 Bedroom')
        ->assertSee('2 Bedroom')
        ->assertSee('Studio');

    $this->assertDatabaseHas('categories', [
        'tenant_id' => $admin->tenant_id,
        'name' => '1 Bedroom',
    ]);
    $this->assertDatabaseHas('categories', [
        'tenant_id' => $admin->tenant_id,
        'name' => '2 Bedroom',
    ]);
    $this->assertDatabaseHas('categories', [
        'tenant_id' => $admin->tenant_id,
        'name' => 'Studio',
    ]);
});

test('unit create form category dropdown is tenant isolated', function () {
    $adminA = User::factory()->admin()->create();
    $tenantB = Tenant::factory()->create(['slug' => 'tenant-b']);

    $tenantACategory = Category::factory()->create([
        'tenant_id' => $adminA->tenant_id,
        'name' => 'Tenant A Exclusive',
        'slug' => 'tenant-a-exclusive',
    ]);

    Category::query()
        ->withoutGlobalScope('tenant')
        ->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Tenant B Exclusive',
            'slug' => 'tenant-b-exclusive',
        ]);

    app(TenantManager::class)->setCurrent($adminA->tenant);

    $this->actingAs($adminA)
        ->get(route('admin.units.create'))
        ->assertOk()
        ->assertSee($tenantACategory->name)
        ->assertDontSee('Tenant B Exclusive');
});
