<?php

use App\Livewire\Admin\Units\Form as UnitForm;
use App\Models\Category;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function fakeOpenAiUnitDescriptionResponse(): void
{
    Http::fake([
        'https://api.openai.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'tagline' => 'A calm, bright condo with practical city living appeal.',
                        'highlights' => [
                            'Natural light through wide windows',
                            'Clean modern interior finishes',
                            'Efficient layout for daily comfort',
                        ],
                        'full_description' => 'This unit offers a balanced mix of comfort and convenience, with practical spaces and a warm atmosphere suited for daily living.',
                        'detected_visual_features' => [
                            'bright windows',
                            'modern finishes',
                            'clean interior',
                        ],
                        'warnings' => [],
                    ]),
                ],
            ]],
        ], 200),
    ]);
}

function addStoredUnitImage(Unit $unit, string $filename = 'sample.jpg'): UnitImage
{
    $uploaded = UploadedFile::fake()->image($filename, 640, 480);
    $path = "units/{$unit->id}/{$filename}";

    Storage::disk('public')->put($path, file_get_contents($uploaded->getRealPath()));

    return UnitImage::create([
        'unit_id' => $unit->id,
        'path' => $path,
        'sort_order' => 0,
    ]);
}

test('non-admin cannot generate ai description', function () {
    Storage::fake('public');
    config(['services.openai.key' => 'test-key', 'services.openai.model' => 'gpt-4.1-mini']);

    $nonAdmin = User::factory()->create(['is_admin' => false]);
    $unit = Unit::factory()->create();
    addStoredUnitImage($unit);

    $this->actingAs($nonAdmin)
        ->get(route('admin.units.edit', ['unit' => $unit]))
        ->assertForbidden();
});

test('admin can generate ai description when unit has images', function () {
    Storage::fake('public');
    config(['services.openai.key' => 'test-key', 'services.openai.model' => 'gpt-4.1-mini']);
    Http::preventStrayRequests();
    fakeOpenAiUnitDescriptionResponse();

    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create([
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
        'description' => 'Original description',
    ]);
    addStoredUnitImage($unit);
    RateLimiter::clear('unit-ai-description:'.$admin->id);

    $component = Livewire::actingAs($admin)
        ->test(UnitForm::class, ['unit' => $unit])
        ->set('description', 'Original description')
        ->set('aiTone', 'Luxury')
        ->set('aiLength', 'Medium')
        ->call('generateAiDescription')
        ->assertHasNoErrors()
        ->assertSet('description', 'Original description');

    expect($component->get('ai_description_draft'))->not->toBeNull();
    Http::assertSentCount(1);
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'AI_DESCRIPTION_GENERATED',
        'unit_id' => $unit->id,
        'user_id' => $admin->id,
    ]);
});

test('admin can generate ai description in create form with newly uploaded photos before save', function () {
    Storage::fake('public');
    config(['services.openai.key' => 'test-key', 'services.openai.model' => 'gpt-4.1-mini']);
    Http::preventStrayRequests();
    fakeOpenAiUnitDescriptionResponse();

    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();
    $upload = UploadedFile::fake()->image('create-form.jpg', 640, 480);

    $component = Livewire::actingAs($admin)
        ->test(UnitForm::class)
        ->set('name', 'Unsaved Draft Unit')
        ->set('category_id', (string) $category->id)
        ->set('location', 'Makati')
        ->set('newImages', [$upload])
        ->call('generateAiDescription')
        ->assertHasNoErrors();

    expect($component->get('ai_description_draft'))->not->toBeNull();
    Http::assertSentCount(1);
});

test('validation fails when no unit images are available', function () {
    Storage::fake('public');
    config(['services.openai.key' => 'test-key', 'services.openai.model' => 'gpt-4.1-mini']);
    Http::preventStrayRequests();
    Http::fake();

    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create([
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    RateLimiter::clear('unit-ai-description:'.$admin->id);

    Livewire::actingAs($admin)
        ->test(UnitForm::class, ['unit' => $unit])
        ->call('generateAiDescription')
        ->assertHasErrors(['ai_generation' => 'Please upload at least 1 photo before generating.']);

    Http::assertNothingSent();
});

test('ai description generation is rate limited per admin user', function () {
    Storage::fake('public');
    config(['services.openai.key' => 'test-key', 'services.openai.model' => 'gpt-4.1-mini']);
    Http::preventStrayRequests();
    fakeOpenAiUnitDescriptionResponse();

    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create([
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    addStoredUnitImage($unit);

    $rateKey = 'unit-ai-description:'.$admin->id;
    RateLimiter::clear($rateKey);

    $component = Livewire::actingAs($admin)
        ->test(UnitForm::class, ['unit' => $unit]);

    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $component->call('generateAiDescription')
            ->assertHasNoErrors();
    }

    $component->call('generateAiDescription')
        ->assertHasErrors(['ai_generation' => 'Too many attempts. Please try again in a minute.']);

    Http::assertSentCount(10);
    RateLimiter::clear($rateKey);
});

test('apply to description copies draft into livewire description field', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create([
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
        'description' => 'Existing persisted description',
    ]);

    Livewire::actingAs($admin)
        ->test(UnitForm::class, ['unit' => $unit])
        ->set('ai_description_draft', "Preview line\n\n- Bullet item")
        ->call('applyAiDescriptionDraft')
        ->assertSet('description', "Preview line\n\n- Bullet item");

    $unit->refresh();
    expect($unit->description)->toBe('Existing persisted description');
});
