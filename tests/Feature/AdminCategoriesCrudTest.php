<?php

use App\Livewire\Admin\Categories\Index as CategoriesIndex;
use App\Models\Category;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

test('admin can create and update categories', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(CategoriesIndex::class)
        ->set('name', 'Penthouse')
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::query()->where('name', 'Penthouse')->firstOrFail();
    expect($category->slug)->toContain('penthouse');

    Livewire::test(CategoriesIndex::class)
        ->call('edit', $category->id)
        ->set('name', 'Luxury Penthouse')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Luxury Penthouse']);
});

test('cannot delete category when units exist', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    Unit::factory()->create(['category_id' => $category->id]);

    $this->actingAs($admin);

    Livewire::test(CategoriesIndex::class)
        ->call('delete', $category->id)
        ->assertHasErrors('name');

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

test('cannot create duplicate category name within the same tenant', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->admin()->create(['tenant_id' => $tenant->id]);
    Category::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Penthouse',
        'slug' => 'penthouse',
    ]);

    $this->actingAs($admin);

    Livewire::test(CategoriesIndex::class)
        ->set('name', 'Penthouse')
        ->call('save')
        ->assertHasErrors('name');
});
