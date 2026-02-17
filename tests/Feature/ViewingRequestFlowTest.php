<?php

use App\Models\Unit;
use App\Models\User;
use App\Models\ViewingRequest;
use App\Notifications\ViewingRequested;
use App\Services\ViewingRequestService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

test('viewing request validation requires future start date', function () {
    $unit = Unit::factory()->create();

    $service = app(ViewingRequestService::class);

    expect(fn () => $service->create([
        'unit_id' => $unit->id,
        'requested_start_at' => CarbonImmutable::now()->subHour()->toDateTimeString(),
        'requested_end_at' => CarbonImmutable::now()->toDateTimeString(),
        'requester_name' => 'Client User',
        'requester_email' => 'client@example.com',
    ]))->toThrow(ValidationException::class);
});

test('viewing request creates audit log and notifies admins', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create();
    $start = CarbonImmutable::now()->addDays(2)->setTime(10, 0);

    $service = app(ViewingRequestService::class);
    $created = $service->create([
        'unit_id' => $unit->id,
        'requested_start_at' => $start->toDateTimeString(),
        'requested_end_at' => $start->addHour()->toDateTimeString(),
        'requester_name' => 'Client User',
        'requester_email' => 'client@example.com',
    ]);

    expect($created->status)->toBe(ViewingRequest::STATUS_PENDING);

    $this->assertDatabaseHas('audit_logs', [
        'unit_id' => $unit->id,
        'action' => 'VIEWING_REQUEST_CREATED',
    ]);

    Notification::assertSentTo($admin, ViewingRequested::class);
});

test('confirmed viewing request overlap is prevented', function () {
    $unit = Unit::factory()->create();
    $start = CarbonImmutable::now()->addDays(3)->setTime(14, 0);

    ViewingRequest::factory()->create([
        'unit_id' => $unit->id,
        'requested_start_at' => $start->toDateTimeString(),
        'requested_end_at' => $start->addHour()->toDateTimeString(),
        'status' => ViewingRequest::STATUS_CONFIRMED,
    ]);

    $service = app(ViewingRequestService::class);

    expect(fn () => $service->create([
        'unit_id' => $unit->id,
        'requested_start_at' => $start->addMinutes(30)->toDateTimeString(),
        'requested_end_at' => $start->addHours(2)->toDateTimeString(),
        'requester_name' => 'Overlap User',
        'requester_email' => 'overlap@example.com',
    ]))->toThrow(ValidationException::class);
});

test('viewing request requires at least one contact method', function () {
    $unit = Unit::factory()->create();
    $start = CarbonImmutable::now()->addDays(2)->setTime(9, 0);

    $service = app(ViewingRequestService::class);

    expect(fn () => $service->create([
        'unit_id' => $unit->id,
        'requested_start_at' => $start->toDateTimeString(),
        'requested_end_at' => $start->addHour()->toDateTimeString(),
        'requester_name' => 'No Contact User',
    ]))->toThrow(ValidationException::class);
});

test('service ignores caller supplied ip and uses request ip', function () {
    $unit = Unit::factory()->create();
    $start = CarbonImmutable::now()->addDays(4)->setTime(11, 0);
    $request = Request::create(
        uri: '/units/'.$unit->id.'/viewing-request',
        method: 'POST',
        server: ['REMOTE_ADDR' => '203.0.113.55']
    );

    $service = app(ViewingRequestService::class);
    $created = $service->create([
        'unit_id' => $unit->id,
        'requested_start_at' => $start->toDateTimeString(),
        'requested_end_at' => $start->addHour()->toDateTimeString(),
        'requester_name' => 'IP Test',
        'requester_email' => 'iptest@example.com',
        'ip_address' => '8.8.8.8',
    ], $request);

    expect($created->ip_address)->toBe('203.0.113.55');
});

test('non-admin cannot confirm viewing requests via service even if called directly', function () {
    $nonAdmin = User::factory()->create(['is_admin' => false]);
    $requestRecord = ViewingRequest::factory()->create([
        'status' => ViewingRequest::STATUS_PENDING,
    ]);

    $request = Request::create(
        uri: '/admin/viewing-requests/'.$requestRecord->id.'/confirm',
        method: 'POST'
    );
    $request->setUserResolver(fn () => $nonAdmin);

    $service = app(ViewingRequestService::class);

    expect(fn () => $service->confirm($requestRecord, $request))
        ->toThrow(AuthorizationException::class);
});
