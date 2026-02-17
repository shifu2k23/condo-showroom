<?php

use App\Livewire\Public\RenterPortal;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

test('correct rental code grants renter portal access', function () {
    $plainCode = 'ABCD-EFGH-JKLM';
    $unit = Unit::factory()->create();

    $rental = Rental::factory()->create([
        'unit_id' => $unit->id,
        'renter_name' => 'Jane Doe',
        'id_type' => 'PASSPORT',
        'public_code_hash' => Hash::make($plainCode),
        'public_code_last4' => 'JKLM',
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
        ->assertSee('Access verified');

    expect(session('renter_access.rental_id'))->toBe($rental->id);
});

test('wrong rental code is blocked', function () {
    Rental::factory()->create([
        'renter_name' => 'Jane Doe',
        'id_type' => 'PASSPORT',
        'public_code_hash' => Hash::make('ABCD-EFGH-JKLM'),
        'public_code_last4' => 'JKLM',
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);

    Livewire::test(RenterPortal::class)
        ->set('renter_name', 'Jane Doe')
        ->set('id_type', 'PASSPORT')
        ->set('rental_code', 'ABCD-EFGH-AAAA')
        ->call('login')
        ->assertHasErrors(['rental_code']);
});

test('expired rental code is blocked with professional message', function () {
    Rental::factory()->create([
        'renter_name' => 'Expired Renter',
        'id_type' => 'PASSPORT',
        'public_code_hash' => Hash::make('WXYZ-BCDE-FGHJ'),
        'public_code_last4' => 'FGHJ',
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subMinute(),
    ]);

    Livewire::test(RenterPortal::class)
        ->set('renter_name', 'Expired Renter')
        ->set('id_type', 'PASSPORT')
        ->set('rental_code', 'WXYZ-BCDE-FGHJ')
        ->call('login')
        ->assertHasErrors(['rental_code'])
        ->assertSee('This rental access has ended.');
});

test('renter login is rate limited to five attempts per minute per ip', function () {
    $rateKey = 'renter-login:'.hash('sha256', '127.0.0.1');
    RateLimiter::clear($rateKey);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        Livewire::test(RenterPortal::class)
            ->set('renter_name', 'Rate Limited')
            ->set('id_type', 'PASSPORT')
            ->set('rental_code', 'ABCD-EFGH-AAAA')
            ->call('login');
    }

    Livewire::test(RenterPortal::class)
        ->set('renter_name', 'Rate Limited')
        ->set('id_type', 'PASSPORT')
        ->set('rental_code', 'ABCD-EFGH-AAAA')
        ->call('login')
        ->assertHasErrors(['rental_code'])
        ->assertSee('Too many login attempts');
});
