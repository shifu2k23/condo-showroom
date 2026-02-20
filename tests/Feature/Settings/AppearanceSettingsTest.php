<?php

use App\Models\User;

test('appearance settings page renders theme options', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertSee('Light')
        ->assertSee('Dark')
        ->assertSee('System');
});

test('appearance layout does not hardcode light mode override', function () {
    $layout = file_get_contents(resource_path('views/layouts/app/sidebar.blade.php'));

    expect($layout)->not->toContain("localStorage.setItem('flux.appearance', 'light')");
    expect($layout)->not->toContain("window.Flux.applyAppearance('light')");
});
