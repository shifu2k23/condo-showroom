<?php

use App\Livewire\Admin\Rentals\Form as RentalForm;
use App\Models\Rental;
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

    expect($issuedCode)->toMatch('/^[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/');

    $rental = Rental::query()->latest('id')->firstOrFail();
    expect($rental->public_code_hash)->not->toBe($issuedCode);
    expect(Hash::check($issuedCode, $rental->public_code_hash))->toBeTrue();

    $raw = preg_replace('/[^A-Z0-9]/', '', $issuedCode) ?? '';
    expect($rental->public_code_last4)->toBe(substr($raw, -4));
    expect($rental->contact_number)->toBe('+63 912 345 6789');
});

test('issued rental code is shown once on admin listing', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    session()->flash('issued_rental_code', 'ABCD-EFGH-JKLM');

    $this->get(route('admin.rentals.index'))
        ->assertOk()
        ->assertSee('ABCD-EFGH-JKLM');

    $this->get(route('admin.rentals.index'))
        ->assertOk()
        ->assertDontSee('ABCD-EFGH-JKLM');
});
