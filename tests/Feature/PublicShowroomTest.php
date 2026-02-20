<?php

use App\Models\Rental;
use App\Models\Unit;
use App\Models\UnitImage;

test('public showroom returns 200', function () {
    $this->get(route('home'))->assertOk();
});

test('showroom route renders entrance animation shell', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-showroom-entrance="overlay"', false);
});

test('unit detail route does not render showroom entrance shell', function () {
    $unit = Unit::factory()->create();

    $this->get(route('unit.show', ['unit' => $unit->public_id]))
        ->assertOk()
        ->assertDontSee('data-showroom-entrance="overlay"', false);
});

test('public pages do not expose login links', function () {
    $unit = Unit::factory()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('href="'.route('login', absolute: false).'"', false)
        ->assertDontSee('href="/admin"', false);

    $this->get(route('unit.show', ['unit' => $unit->public_id]))
        ->assertOk()
        ->assertDontSee('href="'.route('login', absolute: false).'"', false)
        ->assertDontSee('href="/admin"', false);
});

test('public showroom image URLs use tenant media route', function () {
    $unit = Unit::factory()->create(['status' => Unit::STATUS_AVAILABLE]);

    UnitImage::factory()->create([
        'tenant_id' => $unit->tenant_id,
        'unit_id' => $unit->id,
        'path' => "tenants/{$unit->tenant_id}/units/{$unit->id}/example.jpg",
        'sort_order' => 0,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('/media/unit-images/', false);
});

test('showroom marks unit as rented when there is an active rental window', function () {
    $unit = Unit::factory()->create([
        'name' => 'Rented Unit Demo',
        'status' => Unit::STATUS_AVAILABLE,
    ]);

    Rental::factory()->create([
        'unit_id' => $unit->id,
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHours(6),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Rented Unit Demo')
        ->assertSee('Rented until');
});

test('showroom marks unit as reserved when rental starts in the future', function () {
    $unit = Unit::factory()->create([
        'name' => 'Reserved Unit Demo',
        'status' => Unit::STATUS_AVAILABLE,
    ]);

    Rental::factory()->create([
        'unit_id' => $unit->id,
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->addHours(4),
        'ends_at' => now()->addDay(),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Reserved Unit Demo')
        ->assertSee('Reserved starting');
});

test('showroom displays explicit availability status text on unit cards', function () {
    Unit::factory()->create([
        'name' => 'Available Unit Demo',
        'status' => Unit::STATUS_AVAILABLE,
    ]);

    Unit::factory()->create([
        'name' => 'Unavailable Unit Demo',
        'status' => Unit::STATUS_UNAVAILABLE,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Available Unit Demo')
        ->assertSee('Unavailable Unit Demo')
        ->assertSee('Available')
        ->assertSee('Unavailable');
});
