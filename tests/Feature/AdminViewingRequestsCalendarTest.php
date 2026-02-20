<?php

use App\Livewire\Admin\ViewingRequests\Index as ViewingRequestsIndex;
use App\Models\Unit;
use App\Models\User;
use App\Models\ViewingRequest;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

test('admin viewing requests page renders calendar with database-backed entries', function () {
    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $startA = CarbonImmutable::now()->addDays(3)->setTime(10, 0);
    $startB = CarbonImmutable::now()->addDays(3)->setTime(14, 0);

    ViewingRequest::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'unit_id' => $unit->id,
        'requester_name' => 'Calendar Guest A',
        'requested_start_at' => $startA,
        'requested_end_at' => $startA->addHour(),
        'status' => ViewingRequest::STATUS_PENDING,
    ]);

    ViewingRequest::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'unit_id' => $unit->id,
        'requester_name' => 'Calendar Guest B',
        'requested_start_at' => $startB,
        'requested_end_at' => $startB->addHour(),
        'status' => ViewingRequest::STATUS_CONFIRMED,
    ]);

    $this->actingAs($admin);

    Livewire::test(ViewingRequestsIndex::class)
        ->set('calendarMonth', $startA->format('Y-m'))
        ->assertSee('Viewing Requests Calendar')
        ->assertSee($startA->format('F Y'))
        ->assertSee('Calendar Guest A')
        ->assertSee('Calendar Guest B');
});

test('selecting a calendar day filters requests to that exact date', function () {
    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $selectedDay = CarbonImmutable::now()->addDays(2)->setTime(9, 0);
    $otherDay = CarbonImmutable::now()->addDays(6)->setTime(15, 0);

    ViewingRequest::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'unit_id' => $unit->id,
        'requester_name' => 'Selected Day Guest',
        'requested_start_at' => $selectedDay,
        'requested_end_at' => $selectedDay->addHour(),
        'status' => ViewingRequest::STATUS_PENDING,
    ]);

    ViewingRequest::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'unit_id' => $unit->id,
        'requester_name' => 'Other Day Guest',
        'requested_start_at' => $otherDay,
        'requested_end_at' => $otherDay->addHour(),
        'status' => ViewingRequest::STATUS_PENDING,
    ]);

    $this->actingAs($admin);

    Livewire::test(ViewingRequestsIndex::class)
        ->call('selectCalendarDay', $selectedDay->toDateString())
        ->assertSet('dateFrom', $selectedDay->toDateString())
        ->assertSet('dateTo', $selectedDay->toDateString())
        ->assertSee('Selected Day Guest')
        ->assertDontSee('Other Day Guest');
});
