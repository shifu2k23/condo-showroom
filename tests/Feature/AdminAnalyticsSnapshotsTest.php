<?php

use App\Models\AnalyticsSnapshot;
use App\Models\MaintenanceTicket;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\ViewingRequest;
use Carbon\CarbonImmutable;

test('weekly analytics snapshot is computed correctly', function () {
    $reference = CarbonImmutable::parse('2026-03-18 10:00:00');
    CarbonImmutable::setTestNow($reference);

    $weekStart = $reference->startOfWeek();

    $unitA = Unit::factory()->create([
        'name' => 'Aurora Suite',
        'nightly_price_php' => 2000,
        'monthly_price_php' => 60000,
    ]);

    $unitB = Unit::factory()->create([
        'name' => 'Bayside Loft',
        'nightly_price_php' => 1000,
        'monthly_price_php' => 30000,
    ]);

    ViewingRequest::factory()->for($unitA)->create([
        'status' => ViewingRequest::STATUS_CONFIRMED,
        'requested_start_at' => $weekStart->addDay()->setTime(10, 0),
        'requested_end_at' => $weekStart->addDay()->setTime(11, 0),
    ]);
    ViewingRequest::factory()->for($unitA)->create([
        'status' => ViewingRequest::STATUS_CONFIRMED,
        'requested_start_at' => $weekStart->addDays(2)->setTime(10, 0),
        'requested_end_at' => $weekStart->addDays(2)->setTime(11, 0),
    ]);
    ViewingRequest::factory()->for($unitB)->create([
        'status' => ViewingRequest::STATUS_CANCELLED,
        'requested_start_at' => $weekStart->addDays(3)->setTime(10, 0),
        'requested_end_at' => $weekStart->addDays(3)->setTime(11, 0),
    ]);
    ViewingRequest::factory()->for($unitB)->create([
        'status' => ViewingRequest::STATUS_PENDING,
        'requested_start_at' => $weekStart->addDays(4)->setTime(10, 0),
        'requested_end_at' => $weekStart->addDays(4)->setTime(11, 0),
    ]);

    ViewingRequest::factory()->for($unitA)->create([
        'status' => ViewingRequest::STATUS_CONFIRMED,
        'requested_start_at' => $weekStart->subDay()->setTime(10, 0),
        'requested_end_at' => $weekStart->subDay()->setTime(11, 0),
    ]);

    $rentalA = Rental::factory()->for($unitA)->create([
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => $weekStart->setTime(14, 0),
        'ends_at' => $weekStart->addDays(3)->setTime(12, 0),
    ]);
    Rental::factory()->for($unitB)->create([
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => $weekStart->addDays(2)->setTime(14, 0),
        'ends_at' => $weekStart->addDays(6)->setTime(12, 0),
    ]);
    Rental::factory()->for($unitA)->create([
        'status' => Rental::STATUS_CANCELLED,
        'starts_at' => $weekStart->addDays(4)->setTime(14, 0),
        'ends_at' => $weekStart->addDays(6)->setTime(12, 0),
    ]);

    MaintenanceTicket::factory()->create([
        'rental_id' => $rentalA->id,
        'unit_id' => $unitA->id,
        'status' => MaintenanceTicket::STATUS_CLOSED,
        'created_at' => $weekStart->addDay()->setTime(9, 0),
        'updated_at' => $weekStart->addDays(3)->setTime(9, 0),
    ]);
    MaintenanceTicket::factory()->create([
        'rental_id' => $rentalA->id,
        'unit_id' => $unitA->id,
        'status' => MaintenanceTicket::STATUS_RESOLVED,
        'created_at' => $weekStart->addDays(2)->setTime(9, 0),
        'updated_at' => $weekStart->addDays(5)->setTime(9, 0),
    ]);
    MaintenanceTicket::factory()->create([
        'rental_id' => $rentalA->id,
        'unit_id' => $unitA->id,
        'status' => MaintenanceTicket::STATUS_OPEN,
        'created_at' => $weekStart->addDays(2)->setTime(14, 0),
        'updated_at' => $weekStart->addDays(2)->setTime(14, 0),
    ]);

    $this->artisan('analytics:snapshot', [
        '--period' => 'week',
        '--date' => $reference->toDateString(),
    ])->assertSuccessful();

    $snapshot = AnalyticsSnapshot::query()
        ->where('period_type', 'WEEK')
        ->whereDate('period_start', $weekStart->toDateString())
        ->first();

    expect($snapshot)->not->toBeNull();

    $metrics = $snapshot->metrics;
    expect($metrics['viewing_requests'])->toMatchArray([
        'total' => 4,
        'confirmed' => 2,
        'cancelled' => 1,
        'conversion_rate' => 50.0,
    ]);
    expect($metrics['rentals'])->toMatchArray([
        'nights' => 7,
        'occupancy_rate' => 50.0,
        'revenue_estimate' => 10000.0,
    ]);
    expect($metrics['tickets'])->toMatchArray([
        'open' => 1,
        'closed' => 2,
        'avg_resolution_hours' => 60.0,
    ]);

    $topByRevenue = collect($metrics['top_units_by_revenue']);
    $topByOccupancy = collect($metrics['top_units_by_occupancy']);

    expect($topByRevenue->first())->toMatchArray([
        'unit_id' => $unitA->id,
        'unit_name' => 'Aurora Suite',
        'nights' => 3,
        'revenue_estimate' => 6000.0,
        'occupancy_rate' => 42.86,
    ]);

    expect($topByOccupancy->first())->toMatchArray([
        'unit_id' => $unitB->id,
        'unit_name' => 'Bayside Loft',
        'nights' => 4,
        'revenue_estimate' => 4000.0,
        'occupancy_rate' => 57.14,
    ]);
});

test('monthly analytics snapshot is computed correctly', function () {
    $reference = CarbonImmutable::parse('2026-03-18 10:00:00');
    CarbonImmutable::setTestNow($reference);

    $monthStart = $reference->startOfMonth();

    $unit = Unit::factory()->create([
        'name' => 'Skyline Suite',
        'nightly_price_php' => 3000,
        'monthly_price_php' => 90000,
    ]);

    ViewingRequest::factory()->for($unit)->create([
        'status' => ViewingRequest::STATUS_CONFIRMED,
        'requested_start_at' => $monthStart->addDay()->setTime(10, 0),
        'requested_end_at' => $monthStart->addDay()->setTime(11, 0),
    ]);
    ViewingRequest::factory()->for($unit)->create([
        'status' => ViewingRequest::STATUS_CONFIRMED,
        'requested_start_at' => $monthStart->addDays(3)->setTime(10, 0),
        'requested_end_at' => $monthStart->addDays(3)->setTime(11, 0),
    ]);
    ViewingRequest::factory()->for($unit)->create([
        'status' => ViewingRequest::STATUS_CANCELLED,
        'requested_start_at' => $monthStart->addDays(5)->setTime(10, 0),
        'requested_end_at' => $monthStart->addDays(5)->setTime(11, 0),
    ]);
    ViewingRequest::factory()->for($unit)->create([
        'status' => ViewingRequest::STATUS_PENDING,
        'requested_start_at' => $monthStart->addDays(7)->setTime(10, 0),
        'requested_end_at' => $monthStart->addDays(7)->setTime(11, 0),
    ]);

    $rental = Rental::factory()->for($unit)->create([
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => CarbonImmutable::parse('2026-02-27 14:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-03-03 12:00:00'),
    ]);

    Rental::factory()->for($unit)->create([
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => CarbonImmutable::parse('2026-03-10 14:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-03-15 12:00:00'),
    ]);

    Rental::factory()->for($unit)->create([
        'status' => Rental::STATUS_CANCELLED,
        'starts_at' => CarbonImmutable::parse('2026-03-20 14:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-03-25 12:00:00'),
    ]);

    MaintenanceTicket::factory()->create([
        'rental_id' => $rental->id,
        'unit_id' => $unit->id,
        'status' => MaintenanceTicket::STATUS_OPEN,
        'created_at' => CarbonImmutable::parse('2026-03-10 08:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-03-10 08:00:00'),
    ]);
    MaintenanceTicket::factory()->create([
        'rental_id' => $rental->id,
        'unit_id' => $unit->id,
        'status' => MaintenanceTicket::STATUS_IN_PROGRESS,
        'created_at' => CarbonImmutable::parse('2026-03-11 08:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-03-11 10:00:00'),
    ]);
    MaintenanceTicket::factory()->create([
        'rental_id' => $rental->id,
        'unit_id' => $unit->id,
        'status' => MaintenanceTicket::STATUS_CLOSED,
        'created_at' => CarbonImmutable::parse('2026-03-12 09:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-03-13 09:00:00'),
    ]);

    $this->artisan('analytics:snapshot', [
        '--period' => 'month',
        '--date' => $reference->toDateString(),
    ])->assertSuccessful();

    $snapshot = AnalyticsSnapshot::query()
        ->where('period_type', 'MONTH')
        ->whereDate('period_start', $monthStart->toDateString())
        ->first();

    expect($snapshot)->not->toBeNull();

    $metrics = $snapshot->metrics;
    expect($metrics['viewing_requests'])->toMatchArray([
        'total' => 4,
        'confirmed' => 2,
        'cancelled' => 1,
        'conversion_rate' => 50.0,
    ]);
    expect($metrics['rentals'])->toMatchArray([
        'nights' => 7,
        'occupancy_rate' => 22.58,
        'revenue_estimate' => 21000.0,
    ]);
    expect($metrics['tickets'])->toMatchArray([
        'open' => 2,
        'closed' => 1,
        'avg_resolution_hours' => 24.0,
    ]);

    $topByRevenue = collect($metrics['top_units_by_revenue']);
    expect($topByRevenue)->toHaveCount(1);
    expect($topByRevenue->first())->toMatchArray([
        'unit_id' => $unit->id,
        'unit_name' => 'Skyline Suite',
        'nights' => 7,
        'revenue_estimate' => 21000.0,
        'occupancy_rate' => 22.58,
    ]);
});

