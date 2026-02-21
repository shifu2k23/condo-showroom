<?php

use App\Livewire\Admin\Rentals\Form as RentalForm;
use App\Livewire\Admin\Rentals\Index as RentalsIndex;
use App\Models\MaintenanceTicket;
use App\Models\Rental;
use App\Models\RenterSession;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('admin rental creation generates one-time readable code and stores only hash', function () {
    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create(['created_by' => $admin->id]);
    $this->actingAs($admin);

    Livewire::test(RentalForm::class)
        ->set('unit_id', (string) $unit->id)
        ->set('renter_name', 'Renter Sample')
        ->set('contact_number', '+63 912 345 6789')
        ->set('id_type', 'PASSPORT')
        ->set('id_last4', '1234')
        ->set('starts_at', now()->addHour()->format('Y-m-d\TH:i'))
        ->set('ends_at', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.rentals.index', absolute: false));

    $issuedCode = session('issued_rental_code');

    expect($issuedCode)->toMatch('/^\d{6}$/');

    $rental = Rental::query()->latest('id')->firstOrFail();
    expect($rental->public_code_hash)->not->toBe($issuedCode);
    expect(Hash::check($issuedCode, $rental->public_code_hash))->toBeTrue();

    $raw = preg_replace('/\D+/', '', $issuedCode) ?? '';
    expect($rental->public_code_last4)->toBe(substr($raw, -4));
    expect($rental->contact_number)->toBe('+63 912 345 6789');
});

test('issued rental code is shown once on admin listing', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    session()->flash('issued_rental_code', '123456');

    $this->get(route('admin.rentals.index'))
        ->assertOk()
        ->assertSee('123456');

    $this->get(route('admin.rentals.index'))
        ->assertOk()
        ->assertDontSee('123456');
});

test('admin can update renter details without regenerating access code', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $rental = Rental::factory()->create([
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
        'renter_name' => 'Old Name',
        'contact_number' => '+63 900 000 0000',
        'status' => Rental::STATUS_ACTIVE,
    ]);

    $originalHash = $rental->public_code_hash;
    $originalLast4 = $rental->public_code_last4;

    Livewire::test(RentalForm::class, ['rental' => $rental])
        ->set('renter_name', 'Updated Name')
        ->set('contact_number', '+63 912 111 2222')
        ->set('id_type', 'DRIVER_LICENSE')
        ->set('id_last4', 'AB12')
        ->set('status', Rental::STATUS_CANCELLED)
        ->set('starts_at', now()->addHour()->format('Y-m-d\TH:i'))
        ->set('ends_at', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.rentals.index', absolute: false));

    $rental->refresh();

    expect($rental->renter_name)->toBe('Updated Name');
    expect($rental->contact_number)->toBe('+63 912 111 2222');
    expect($rental->id_type)->toBe('DRIVER_LICENSE');
    expect($rental->id_last4)->toBe('AB12');
    expect($rental->status)->toBe(Rental::STATUS_CANCELLED);
    expect($rental->public_code_hash)->toBe($originalHash);
    expect($rental->public_code_last4)->toBe($originalLast4);
});

test('admin can regenerate renter access code while editing rental', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $rental = Rental::factory()->create([
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
        'public_code_hash' => Hash::make('111111'),
        'public_code_last4' => '1111',
    ]);

    Livewire::test(RentalForm::class, ['rental' => $rental])
        ->set('regenerate_access_code', true)
        ->set('starts_at', now()->addHour()->format('Y-m-d\TH:i'))
        ->set('ends_at', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.rentals.index', absolute: false));

    $issuedCode = session('issued_rental_code');
    expect($issuedCode)->toMatch('/^\d{6}$/');

    $rental->refresh();
    expect(Hash::check($issuedCode, $rental->public_code_hash))->toBeTrue();
    expect($rental->public_code_last4)->toBe(substr($issuedCode, -4));
    expect($rental->public_code_last4)->not->toBe('1111');
});

test('admin can delete rental from listing', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $rental = Rental::factory()->create([
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    Livewire::test(RentalsIndex::class)
        ->call('deleteRental', $rental->id)
        ->assertHasNoErrors();

    expect(Rental::query()->whereKey($rental->id)->exists())->toBeFalse();
});

test('deleting rental cascades renter sessions and maintenance tickets', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $unit = Unit::factory()->create(['created_by' => $admin->id]);
    $rental = Rental::factory()->create([
        'unit_id' => $unit->id,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $session = RenterSession::factory()->create([
        'rental_id' => $rental->id,
    ]);

    $ticket = MaintenanceTicket::factory()->create([
        'rental_id' => $rental->id,
        'unit_id' => $unit->id,
    ]);

    Livewire::test(RentalsIndex::class)
        ->call('deleteRental', $rental->id)
        ->assertHasNoErrors();

    expect(Rental::query()->whereKey($rental->id)->exists())->toBeFalse();
    expect(RenterSession::query()->whereKey($session->id)->exists())->toBeFalse();
    expect(MaintenanceTicket::query()->whereKey($ticket->id)->exists())->toBeFalse();
});

test('admin cannot update rental into overlapping active window for same unit', function () {
    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create(['created_by' => $admin->id]);
    $this->actingAs($admin);

    Rental::factory()->create([
        'unit_id' => $unit->id,
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->addHours(1),
        'ends_at' => now()->addHours(10),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $editableRental = Rental::factory()->create([
        'unit_id' => $unit->id,
        'status' => Rental::STATUS_ACTIVE,
        'starts_at' => now()->addHours(12),
        'ends_at' => now()->addHours(24),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    Livewire::test(RentalForm::class, ['rental' => $editableRental])
        ->set('status', Rental::STATUS_ACTIVE)
        ->set('starts_at', now()->addHours(4)->format('Y-m-d\TH:i'))
        ->set('ends_at', now()->addHours(8)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['starts_at']);
});
