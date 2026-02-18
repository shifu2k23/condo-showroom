<?php

use App\Livewire\Public\RenterPortal;
use App\Models\AuditLog;
use App\Models\Rental;
use App\Models\RenterSession;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('renter portal response disables browser caching', function () {
    $response = $this->get(route('renter.portal'))
        ->assertOk()
        ->assertHeader('Pragma', 'no-cache');

    $cacheControl = (string) $response->headers->get('Cache-Control');
    $expires = (string) $response->headers->get('Expires');

    expect($cacheControl)
        ->toContain('no-store')
        ->toContain('no-cache')
        ->toContain('must-revalidate')
        ->toContain('max-age=0')
        ->toContain('private');
    expect($expires)->not->toBe('');
});

test('successful renter login regenerates session id and writes success audit log', function () {
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

    $initialSessionId = session()->getId();

    Livewire::test(RenterPortal::class)
        ->set('renter_name', 'Jane Doe')
        ->set('id_type', 'PASSPORT')
        ->set('rental_code', $plainCode)
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('renter.dashboard'));

    expect(session()->getId())->not->toBe($initialSessionId);

    $log = AuditLog::query()->where('action', 'RENTER_LOGIN_SUCCESS')->latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log?->changes['rental_id'])->toBe($rental->id);
    expect($log?->changes['code_last4'])->toBe('JKLM');
    expect($log?->changes['renter_session_id'])->toBeInt();
    expect($log?->changes)->not->toHaveKey('rental_code');
    expect($log?->changes)->not->toHaveKey('public_code_hash');
});

test('failed renter login writes audit log without leaking full code', function () {
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

    $log = AuditLog::query()->where('action', 'RENTER_LOGIN_FAILED')->latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log?->changes['reason'])->toBe('credential_mismatch');
    expect($log?->changes['code_last4_input'])->toBe('AAAA');
    expect(json_encode($log?->changes, JSON_THROW_ON_ERROR))->not->toContain('ABCD-EFGH-AAAA');
});

test('renter logout clears renter session data', function () {
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
    ]);

    session()->put('renter_access', [
        'rental_id' => $rental->id,
        'renter_session_id' => RenterSession::factory()->create([
            'rental_id' => $rental->id,
            'token_hash' => hash('sha256', Str::random(80)),
            'expires_at' => now()->addHour(),
        ])->id,
        'token' => 'tampered-token',
        'expires_at' => now()->addHour()->toIso8601String(),
    ]);

    $component = Livewire::test(RenterPortal::class);
    $component->call('logout')->assertHasNoErrors();

    expect(session('renter_access'))->toBeNull();
    $component->assertSee('You have been signed out of the renter portal.');
});
