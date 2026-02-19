<?php

use App\Models\User;

test('admin analytics route is protected for guests and non-admin users', function () {
    $this->get(route('admin.analytics.index'))->assertRedirect(route('login'));

    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.analytics.index'))->assertForbidden();
});

test('admin can view analytics page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.analytics.index'))
        ->assertOk()
        ->assertSee('Analytics');
});

