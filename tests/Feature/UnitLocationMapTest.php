<?php

use App\Livewire\Admin\Units\Form as UnitForm;
use App\Models\Category;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

test('admin can create unit with valid coordinates', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    $this->actingAs($admin);

    Livewire::test(UnitForm::class)
        ->set('name', 'Map Ready Unit')
        ->set('category_id', (string) $category->id)
        ->set('location', 'Pasig City')
        ->set('latitude', '14.5764000')
        ->set('longitude', '121.0851000')
        ->set('address_text', 'Pasig, Metro Manila')
        ->set('description', 'Unit with pinned map location')
        ->set('status', Unit::STATUS_AVAILABLE)
        ->set('nightly_price_php', 3000)
        ->set('monthly_price_php', 42000)
        ->set('price_display_mode', Unit::DISPLAY_NIGHT)
        ->set('estimator_mode', Unit::ESTIMATOR_HYBRID)
        ->set('allow_estimator', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('units', [
        'name' => 'Map Ready Unit',
        'latitude' => '14.5764000',
        'longitude' => '121.0851000',
        'address_text' => 'Pasig, Metro Manila',
    ]);
});

test('admin validation fails for missing or out of range coordinates', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    $this->actingAs($admin);

    Livewire::test(UnitForm::class)
        ->set('name', 'Missing Coordinates Unit')
        ->set('category_id', (string) $category->id)
        ->set('location', 'Quezon City')
        ->set('description', 'No coordinates provided')
        ->set('status', Unit::STATUS_AVAILABLE)
        ->set('nightly_price_php', 2800)
        ->set('monthly_price_php', 39000)
        ->set('price_display_mode', Unit::DISPLAY_NIGHT)
        ->set('estimator_mode', Unit::ESTIMATOR_HYBRID)
        ->set('allow_estimator', true)
        ->call('save')
        ->assertHasErrors(['latitude', 'longitude']);

    Livewire::test(UnitForm::class)
        ->set('name', 'Out of Range Coordinates Unit')
        ->set('category_id', (string) $category->id)
        ->set('location', 'Taguig')
        ->set('latitude', '95')
        ->set('longitude', '190')
        ->set('description', 'Invalid coordinate values')
        ->set('status', Unit::STATUS_AVAILABLE)
        ->set('nightly_price_php', 3200)
        ->set('monthly_price_php', 45000)
        ->set('price_display_mode', Unit::DISPLAY_NIGHT)
        ->set('estimator_mode', Unit::ESTIMATOR_HYBRID)
        ->set('allow_estimator', true)
        ->call('save')
        ->assertHasErrors(['latitude', 'longitude']);
});

test('public unit detail includes google maps urls when coordinates exist', function () {
    $unit = Unit::factory()->create([
        'latitude' => '14.6000000',
        'longitude' => '120.9800000',
    ]);

    $this->get(route('unit.show', $unit))
        ->assertOk()
        ->assertSee('Open in Google Maps')
        ->assertSee($unit->googleMapsUrl(), false)
        ->assertSee('destination=14.6000000,120.9800000', false);
});

test('public unit detail shows location not available when coordinates are missing', function () {
    $unit = Unit::factory()->create([
        'latitude' => null,
        'longitude' => null,
    ]);

    $this->get(route('unit.show', $unit))
        ->assertOk()
        ->assertSee('Location not available.')
        ->assertDontSee('Open in Google Maps');
});

test('public html does not expose secret-like strings', function () {
    $unit = Unit::factory()->create();

    $responses = [
        $this->get(route('home')),
        $this->get(route('unit.show', $unit)),
    ];

    $secretNeedles = [
        'AWS_SECRET',
        'AWS_SECRET_ACCESS_KEY',
        'PUSHER_SECRET',
        'PUSHER_APP_SECRET',
        'MAPBOX_SECRET',
        'CLOUDINARY_API_SECRET',
        'STRIPE_SECRET',
        'OPENAI_API_KEY',
    ];

    foreach ($responses as $response) {
        $html = $response->assertOk()->getContent();

        foreach ($secretNeedles as $needle) {
            expect($html)->not->toContain($needle);
        }
    }
});
