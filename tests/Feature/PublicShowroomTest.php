<?php

use App\Models\Unit;

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
