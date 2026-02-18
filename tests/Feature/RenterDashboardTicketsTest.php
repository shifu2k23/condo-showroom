<?php

use App\Livewire\Public\RenterTickets;
use App\Models\MaintenanceTicket;
use App\Models\Rental;
use App\Models\RenterSession;
use App\Models\Unit;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('renter dashboard requires a valid renter session token', function () {
    $this->get(route('renter.dashboard'))
        ->assertRedirect(route('renter.access'));
});

test('renter dashboard rejects tampered renter session token', function () {
    $unit = Unit::factory()->create();
    $rental = Rental::factory()->create([
        'unit_id' => $unit->id,
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    $session = RenterSession::factory()->create([
        'rental_id' => $rental->id,
        'token_hash' => hash('sha256', Str::random(80)),
        'expires_at' => now()->addHour(),
    ]);

    $this->withSession([
        'renter_access' => [
            'rental_id' => $rental->id,
            'renter_session_id' => $session->id,
            'token' => 'INVALID-TOKEN',
            'expires_at' => now()->addHour()->toIso8601String(),
        ],
    ])->get(route('renter.dashboard'))
        ->assertRedirect(route('renter.access'));
});

test('renter with valid token can view dashboard', function () {
    $unit = Unit::factory()->create();
    $rental = Rental::factory()->create([
        'unit_id' => $unit->id,
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    $plainToken = Str::random(80);
    $session = RenterSession::factory()->create([
        'rental_id' => $rental->id,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addHour(),
    ]);

    $this->withSession([
        'renter_access' => [
            'rental_id' => $rental->id,
            'renter_session_id' => $session->id,
            'token' => $plainToken,
            'expires_at' => now()->addHour()->toIso8601String(),
        ],
    ])->get(route('renter.dashboard'))
        ->assertOk()
        ->assertSee('Renter Dashboard')
        ->assertSee($rental->unit?->name ?? '');
});

test('ticket creation works for active rental session', function () {
    $unit = Unit::factory()->create();
    $rental = Rental::factory()->create([
        'unit_id' => $unit->id,
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    $plainToken = Str::random(80);
    $session = RenterSession::factory()->create([
        'rental_id' => $rental->id,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addHour(),
    ]);

    session()->put('renter_access', [
        'rental_id' => $rental->id,
        'renter_session_id' => $session->id,
        'token' => $plainToken,
        'expires_at' => now()->addHour()->toIso8601String(),
    ]);

    Livewire::test(RenterTickets::class)
        ->set('category', MaintenanceTicket::CATEGORY_PLUMBING)
        ->set('subject', 'Leaking sink')
        ->set('description', 'The kitchen sink leaks under the cabinet.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Maintenance ticket submitted successfully.');

    $this->assertDatabaseHas('maintenance_tickets', [
        'rental_id' => $rental->id,
        'unit_id' => $unit->id,
        'category' => MaintenanceTicket::CATEGORY_PLUMBING,
        'status' => MaintenanceTicket::STATUS_OPEN,
        'subject' => 'Leaking sink',
    ]);
});

test('ticket can be created through renter tickets post route for active session', function () {
    $unit = Unit::factory()->create();
    $rental = Rental::factory()->create([
        'unit_id' => $unit->id,
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    $plainToken = Str::random(80);
    $session = RenterSession::factory()->create([
        'rental_id' => $rental->id,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addHour(),
    ]);

    $this->withSession([
        'renter_access' => [
            'rental_id' => $rental->id,
            'renter_session_id' => $session->id,
            'token' => $plainToken,
            'expires_at' => now()->addHour()->toIso8601String(),
        ],
    ])->post(route('renter.tickets.store'), [
        'category' => MaintenanceTicket::CATEGORY_ELECTRICAL,
        'subject' => 'Outlet issue',
        'description' => 'One wall outlet is not working.',
    ])->assertRedirect(route('renter.tickets'));

    $this->assertDatabaseHas('maintenance_tickets', [
        'rental_id' => $rental->id,
        'category' => MaintenanceTicket::CATEGORY_ELECTRICAL,
        'subject' => 'Outlet issue',
        'status' => MaintenanceTicket::STATUS_OPEN,
    ]);
});

test('ticket creation is blocked when rental period is expired', function () {
    $unit = Unit::factory()->create();
    $rental = Rental::factory()->create([
        'unit_id' => $unit->id,
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subMinute(),
    ]);

    $plainToken = Str::random(80);
    $session = RenterSession::factory()->create([
        'rental_id' => $rental->id,
        'token_hash' => hash('sha256', $plainToken),
        'expires_at' => now()->addHour(),
    ]);

    session()->put('renter_access', [
        'rental_id' => $rental->id,
        'renter_session_id' => $session->id,
        'token' => $plainToken,
        'expires_at' => now()->addHour()->toIso8601String(),
    ]);

    Livewire::test(RenterTickets::class)
        ->set('category', MaintenanceTicket::CATEGORY_CLEANING)
        ->set('subject', 'Deep cleaning request')
        ->set('description', 'Requesting deep cleaning for the living room.')
        ->call('submit')
        ->assertHasErrors(['subject'])
        ->assertSee('Your rental period has ended. Ticket submission is unavailable.');

    $this->assertDatabaseMissing('maintenance_tickets', [
        'rental_id' => $rental->id,
        'subject' => 'Deep cleaning request',
    ]);
});
