<?php

use App\Models\Unit;
use App\Models\UnitImage;

test('public showroom returns 200', function () {
    $this->get(route('home'))->assertOk();
});

test('public pages do not expose login links', function () {
    $unit = Unit::factory()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('href="'.route('login', absolute: false).'"', false)
        ->assertDontSee('href="/admin"', false);

    $this->get(route('unit.show', $unit))
        ->assertOk()
        ->assertDontSee('href="'.route('login', absolute: false).'"', false)
        ->assertDontSee('href="/admin"', false);
});

test('public showroom image URLs use storage-relative path', function () {
    $unit = Unit::factory()->create(['status' => Unit::STATUS_AVAILABLE]);

    UnitImage::factory()->create([
        'unit_id' => $unit->id,
        'path' => 'units/test/example.jpg',
        'sort_order' => 0,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('src="/storage/units/test/example.jpg"', false);
});
