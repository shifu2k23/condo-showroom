<?php

use App\Livewire\Super\Tenants\Index as SuperTenantsIndex;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

function createTenantUnit(Tenant $tenant, string $name): Unit {
    $category = Category::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => "{$name} Category",
        'slug' => Str::slug($name).'-category',
    ]);

    return Unit::factory()->create([
        'tenant_id' => $tenant->id,
        'category_id' => $category->id,
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
    ]);
}

test('public showroom only shows units for current tenant', function () {
    $tenantA = Tenant::factory()->create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
    $tenantB = Tenant::factory()->create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

    $unitA = createTenantUnit($tenantA, 'A Unit');
    $unitB = createTenantUnit($tenantB, 'B Unit');

    $this->get(route('home', ['tenant' => $tenantA->slug]))
        ->assertOk()
        ->assertSee($unitA->name)
        ->assertDontSee($unitB->name);
});

test('tenant admin cannot access another tenant admin pages', function () {
    $tenantA = Tenant::factory()->create(['slug' => 'tenant-a']);
    $tenantB = Tenant::factory()->create(['slug' => 'tenant-b']);

    $adminA = User::factory()->admin()->create([
        'tenant_id' => $tenantA->id,
        'is_super_admin' => false,
    ]);

    createTenantUnit($tenantA, 'A Unit');
    $unitB = createTenantUnit($tenantB, 'B Unit');

    $this->actingAs($adminA)
        ->get(route('admin.units.index', ['tenant' => $tenantA->slug]))
        ->assertOk()
        ->assertDontSee($unitB->name);

    $this->actingAs($adminA)
        ->get(route('admin.units.index', ['tenant' => $tenantB->slug]))
        ->assertForbidden();
});

test('unit image media endpoint is tenant isolated', function () {
    Storage::fake('local');

    $tenantA = Tenant::factory()->create(['slug' => 'tenant-a']);
    $tenantB = Tenant::factory()->create(['slug' => 'tenant-b']);

    $unitA = createTenantUnit($tenantA, 'A Unit');
    $unitB = createTenantUnit($tenantB, 'B Unit');

    $imageA = UnitImage::factory()->create([
        'tenant_id' => $tenantA->id,
        'unit_id' => $unitA->id,
        'public_id' => (string) Str::ulid(),
        'path' => "tenants/{$tenantA->id}/units/{$unitA->id}/a.jpg",
    ]);

    $imageB = UnitImage::factory()->create([
        'tenant_id' => $tenantB->id,
        'unit_id' => $unitB->id,
        'public_id' => (string) Str::ulid(),
        'path' => "tenants/{$tenantB->id}/units/{$unitB->id}/b.jpg",
    ]);

    Storage::disk('local')->put($imageA->path, 'image-a');
    Storage::disk('local')->put($imageB->path, 'image-b');

    $this->get(route('tenant.media.unit-images.show', [
        'tenant' => $tenantA->slug,
        'unitImage' => $imageA->public_id,
    ]))->assertOk();

    $this->get(route('tenant.media.unit-images.show', [
        'tenant' => $tenantA->slug,
        'unitImage' => $imageB->public_id,
    ]))->assertNotFound();
});

test('super admin can open tenant management and create tenant with initial admin', function () {
    $superAdmin = User::factory()->superAdmin()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($superAdmin)
        ->get(route('super.tenants.index'))
        ->assertOk();

    Livewire::actingAs($superAdmin)
        ->test(SuperTenantsIndex::class)
        ->set('name', 'Client One')
        ->set('slug', 'client-one')
        ->set('createAdmin', true)
        ->set('adminName', 'Client Admin')
        ->set('adminEmail', 'client-admin@example.com')
        ->call('createTenant')
        ->assertHasNoErrors();

    $tenant = Tenant::query()->where('slug', 'client-one')->first();

    expect($tenant)->not->toBeNull();
    $this->assertDatabaseHas('users', [
        'tenant_id' => $tenant->id,
        'email' => 'client-admin@example.com',
        'is_admin' => true,
        'is_super_admin' => false,
    ]);
});

test('login chooser redirects by provided tenant slug without exposing tenant list', function () {
    Tenant::factory()->create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
    Tenant::factory()->create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

    $this->get(route('tenant.login.chooser'))
        ->assertOk()
        ->assertDontSee('/t/tenant-a/login')
        ->assertDontSee('/t/tenant-b/login');

    $this->post(route('tenant.login.redirect'), [
        'tenant_slug' => 'tenant-a',
    ])->assertRedirect(route('login', ['tenant' => 'tenant-a']));
});
