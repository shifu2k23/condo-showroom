<?php

use App\Models\Category;
use App\Models\Unit;
use App\Models\User;
use App\Models\ViewingRequest;
use Illuminate\Support\Facades\Gate;

test('non-admin is denied by admin gates and policies', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $unit = Unit::factory()->create();
    $viewingRequest = ViewingRequest::factory()->create();

    expect(Gate::forUser($user)->allows('access-admin'))->toBeFalse();
    expect(Gate::forUser($user)->allows('create', Category::class))->toBeFalse();
    expect(Gate::forUser($user)->allows('setAvailability', $unit))->toBeFalse();
    expect(Gate::forUser($user)->allows('confirm', $viewingRequest))->toBeFalse();
});

test('admin is allowed by admin gates and policies', function () {
    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create();
    $viewingRequest = ViewingRequest::factory()->create();

    expect(Gate::forUser($admin)->allows('access-admin'))->toBeTrue();
    expect(Gate::forUser($admin)->allows('create', Category::class))->toBeTrue();
    expect(Gate::forUser($admin)->allows('setAvailability', $unit))->toBeTrue();
    expect(Gate::forUser($admin)->allows('confirm', $viewingRequest))->toBeTrue();
});
