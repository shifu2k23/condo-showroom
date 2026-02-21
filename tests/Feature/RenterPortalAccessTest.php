<?php

use App\Livewire\Public\RenterPortal;
use App\Models\Rental;
use App\Models\RenterSession;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

test('correct rental code grants renter portal access', function () {
    $plainCode = '123456';
    $unit = Unit::factory()->create();

    $rental = Rental::factory()->create([
        'unit_id' => $unit->id,
        'renter_name' => 'Jane Doe',
        'id_type' => 'PASSPORT',
        'public_code_hash' => Hash::make($plainCode),
        'public_code_last4' => '3456',
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'created_by' => User::factory()->admin()->create()->id,
    ]);

    Livewire::test(RenterPortal::class)
        ->set('renter_name', 'Jane Doe')
        ->set('id_type', 'PASSPORT')
        ->set('rental_code', $plainCode)
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('renter.dashboard'));

    expect(session('renter_access.rental_id'))->toBe($rental->id);
    expect(session('renter_access.token'))->toBeString()->not->toBe('');
    expect(session('renter_access.renter_session_id'))->toBeInt();

    $session = RenterSession::query()->find(session('renter_access.renter_session_id'));
    expect($session)->not->toBeNull();
    expect($session?->rental_id)->toBe($rental->id);
});

test('wrong rental code is blocked', function () {
    Rental::factory()->create([
        'renter_name' => 'Jane Doe',
        'id_type' => 'PASSPORT',
        'public_code_hash' => Hash::make('123456'),
        'public_code_last4' => '3456',
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    Livewire::test(RenterPortal::class)
        ->set('renter_name', 'Jane Doe')
        ->set('id_type', 'PASSPORT')
        ->set('rental_code', '999999')
        ->call('login')
        ->assertHasErrors(['rental_code']);
});

test('expired rental code is blocked with professional message', function () {
    Rental::factory()->create([
        'renter_name' => 'Expired Renter',
        'id_type' => 'PASSPORT',
        'public_code_hash' => Hash::make('654321'),
        'public_code_last4' => '4321',
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subMinute(),
    ]);

    Livewire::test(RenterPortal::class)
        ->set('renter_name', 'Expired Renter')
        ->set('id_type', 'PASSPORT')
        ->set('rental_code', '654321')
        ->call('login')
        ->assertHasErrors(['rental_code'])
        ->assertSee('Access unavailable. Our records show there is no active rental under these details.');
});

test('renter login is rate limited to five attempts per minute per ip', function () {
    $rateKey = 'renter-login:'.hash('sha256', '127.0.0.1');
    RateLimiter::clear($rateKey);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        Livewire::test(RenterPortal::class)
            ->set('renter_name', 'Rate Limited')
            ->set('id_type', 'PASSPORT')
            ->set('rental_code', '999999')
            ->call('login');
    }

    Livewire::test(RenterPortal::class)
        ->set('renter_name', 'Rate Limited')
        ->set('id_type', 'PASSPORT')
        ->set('rental_code', '999999')
        ->call('login')
        ->assertHasErrors(['rental_code'])
        ->assertSee('Too many login attempts');
});
