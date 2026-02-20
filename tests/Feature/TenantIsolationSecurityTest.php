<?php

use App\Livewire\Super\Tenants\Index as SuperTenantsIndex;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\MaintenanceTicket;
use App\Models\Rental;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Models\User;
use App\Support\Tenancy\TenantManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

function createTenantCategoryAndUnit(Tenant $tenant, string $name): Unit {
    $category = Category::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => "{$name} Category",
        'slug' => Str::slug($name).'-category-'.Str::lower(Str::random(4)),
    ]);

    return Unit::factory()->create([
        'tenant_id' => $tenant->id,
        'public_id' => (string) Str::ulid(),
        'category_id' => $category->id,
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
    ]);
}

test('public tenant A cannot see tenant B units', function () {
    $tenantA = Tenant::factory()->create(['slug' => 'tenant-a']);
    $tenantB = Tenant::factory()->create(['slug' => 'tenant-b']);

    $unitA = createTenantCategoryAndUnit($tenantA, 'A Unit');
    $unitB = createTenantCategoryAndUnit($tenantB, 'B Unit');

    $this->get(route('home', ['tenant' => $tenantA->slug]))
        ->assertOk()
        ->assertSee($unitA->name)
        ->assertDontSee($unitB->name);

    $this->get(route('unit.show', [
        'tenant' => $tenantA->slug,
        'unit' => $unitB->public_id,
    ]))->assertNotFound();
});

test('tenant A admin cannot edit tenant B unit', function () {
    $tenantA = Tenant::factory()->create(['slug' => 'tenant-a']);
    $tenantB = Tenant::factory()->create(['slug' => 'tenant-b']);

    $adminA = User::factory()->admin()->create([
        'tenant_id' => $tenantA->id,
        'is_super_admin' => false,
    ]);

    $unitB = createTenantCategoryAndUnit($tenantB, 'B Unit');

    $response = $this->actingAs($adminA)->get(route('admin.units.edit', [
        'unit' => $unitB->public_id,
    ]));

    expect([403, 404])->toContain($response->getStatusCode());
});

test('global tenant scoping applies to category rental maintenance ticket and audit log queries', function () {
    $tenantA = Tenant::factory()->create(['slug' => 'tenant-a']);
    $tenantB = Tenant::factory()->create(['slug' => 'tenant-b']);

    $categoryA = Category::factory()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Category A',
        'slug' => 'category-a',
    ]);
    $categoryB = Category::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Category B',
        'slug' => 'category-b',
    ]);

    $unitA = Unit::factory()->create([
        'tenant_id' => $tenantA->id,
        'category_id' => $categoryA->id,
    ]);
    $unitB = Unit::factory()->create([
        'tenant_id' => $tenantB->id,
        'category_id' => $categoryB->id,
    ]);

    $rentalA = Rental::factory()->create([
        'tenant_id' => $tenantA->id,
        'unit_id' => $unitA->id,
        'created_by' => null,
        'updated_by' => null,
    ]);
    $rentalB = Rental::factory()->create([
        'tenant_id' => $tenantB->id,
        'unit_id' => $unitB->id,
        'created_by' => null,
        'updated_by' => null,
    ]);

    $ticketA = MaintenanceTicket::factory()->create([
        'tenant_id' => $tenantA->id,
        'rental_id' => $rentalA->id,
        'unit_id' => $unitA->id,
    ]);
    $ticketB = MaintenanceTicket::factory()->create([
        'tenant_id' => $tenantB->id,
        'rental_id' => $rentalB->id,
        'unit_id' => $unitB->id,
    ]);

    $logA = AuditLog::query()->create([
        'tenant_id' => $tenantA->id,
        'unit_id' => $unitA->id,
        'user_id' => null,
        'action' => 'A_ACTION',
        'changes' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);
    $logB = AuditLog::query()->create([
        'tenant_id' => $tenantB->id,
        'unit_id' => $unitB->id,
        'user_id' => null,
        'action' => 'B_ACTION',
        'changes' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);

    app(TenantManager::class)->setCurrent($tenantA);

    expect(Category::query()->pluck('id')->all())->toContain($categoryA->id)->not->toContain($categoryB->id);
    expect(Rental::query()->pluck('id')->all())->toContain($rentalA->id)->not->toContain($rentalB->id);
    expect(MaintenanceTicket::query()->pluck('id')->all())->toContain($ticketA->id)->not->toContain($ticketB->id);
    expect(AuditLog::query()->pluck('id')->all())->toContain($logA->id)->not->toContain($logB->id);
});

test('tenant A cannot access tenant B media image stream', function () {
    Storage::fake('local');

    $tenantA = Tenant::factory()->create(['slug' => 'tenant-a']);
    $tenantB = Tenant::factory()->create(['slug' => 'tenant-b']);

    $unitA = createTenantCategoryAndUnit($tenantA, 'A Unit');
    $unitB = createTenantCategoryAndUnit($tenantB, 'B Unit');

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

test('super admin can create tenant with admin and sees shareable link', function () {
    $superAdmin = User::factory()->superAdmin()->create([
        'email_verified_at' => now(),
    ]);

    Livewire::actingAs($superAdmin)
        ->test(SuperTenantsIndex::class)
        ->set('name', 'Client One')
        ->set('trialDays', 30)
        ->set('createAdmin', true)
        ->set('adminName', 'Client Admin')
        ->set('adminEmail', 'client-admin@example.com')
        ->call('createTenant')
        ->assertHasNoErrors();

    $tenant = Tenant::query()->where('slug', 'client-one')->firstOrFail();

    $this->assertDatabaseHas('users', [
        'tenant_id' => $tenant->id,
        'email' => 'client-admin@example.com',
        'is_admin' => true,
        'is_super_admin' => false,
    ]);

    $this->actingAs($superAdmin)
        ->get(route('super.tenants.index'))
        ->assertOk()
        ->assertSee(rtrim((string) config('app.url'), '/').'/t/client-one');
});

test('disabled tenant blocks public and admin routes', function () {
    $disabledTenant = Tenant::factory()->create([
        'slug' => 'disabled-tenant',
        'is_disabled' => true,
    ]);

    $admin = User::factory()->admin()->create([
        'tenant_id' => $disabledTenant->id,
        'is_super_admin' => false,
    ]);

    $this->get(route('home', ['tenant' => $disabledTenant->slug]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertNotFound();
});
