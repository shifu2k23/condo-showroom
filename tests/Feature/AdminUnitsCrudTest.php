<?php

use App\Livewire\Admin\Units\Form as UnitForm;
use App\Livewire\Admin\Units\Index as UnitsIndex;
use App\Models\Category;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

test('admin can create and update units', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();

    $this->actingAs($admin);

    Livewire::test(UnitForm::class)
        ->set('name', 'Sunrise Condo')
        ->set('category_id', (string) $category->id)
        ->set('location', 'Makati')
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
