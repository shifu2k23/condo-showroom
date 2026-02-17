<?php

use App\Models\User;

test('admin routes redirect guests to login', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->get(route('admin.units.index'))->assertRedirect(route('login'));
    $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
});

test('non admin users receive 403 on admin routes', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.dashboard'))->assertForbidden();
    $this->get(route('admin.units.index'))->assertForbidden();
    $this->get(route('admin.categories.index'))->assertForbidden();
});
