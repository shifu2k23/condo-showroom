<?php

use App\Livewire\Public\UnitShow;
use App\Models\Unit;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

test('public viewing request submission is rate limited per ip and unit', function () {
    $unit = Unit::factory()->create();
    $fingerprint = hash('sha256', '127.0.0.1|'.$unit->id);
    RateLimiter::clear('viewing-request:minute:'.$fingerprint);
    RateLimiter::clear('viewing-request:hour:'.$fingerprint);

    $component = Livewire::test(UnitShow::class, ['unit' => $unit]);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $component
            ->set('formLoadedAt', now()->subSeconds(5)->timestamp)
            ->set('requestDate', now()->addDays(5)->format('Y-m-d'))
            ->set('requestTime', '09:00')
            ->set('clientName', 'Rate Limit User')
            ->set('clientEmail', "ratelimit{$attempt}@example.com")
            ->set('clientPhone', null)
            ->call('submitRequest')
            ->assertSet('requestSuccess', true)
            ->set('requestSuccess', false);
    }

    $component
        ->set('formLoadedAt', now()->subSeconds(5)->timestamp)
        ->set('requestDate', now()->addDays(5)->format('Y-m-d'))
        ->set('requestTime', '09:00')
        ->set('clientName', 'Rate Limit User')
        ->set('clientEmail', 'ratelimit-final@example.com')
        ->set('clientPhone', null)
        ->call('submitRequest')
        ->assertHasErrors(['requestDate']);
});
