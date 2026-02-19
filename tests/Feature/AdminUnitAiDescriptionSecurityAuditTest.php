<?php

use App\Livewire\Admin\Units\Form as UnitForm;
use App\Models\AuditLog;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function fakeOpenAiSecurityAuditSuccess(): void
{
    Http::fake([
        'https://api.openai.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'tagline' => 'Refined city condo with practical comfort.',
                        'highlights' => [
                            'Bright interior with natural daylight',
                            'Functional open-plan arrangement',
                            'Clean, modern finishes',
                        ],
                        'full_description' => 'This unit blends everyday comfort and practical city living with a bright and clean interior suitable for short or long stays.',
                        'detected_visual_features' => [
                            'natural light',
                            'modern finishes',
                            'clean layout',
                        ],
                        'warnings' => [],
                    ]),
                ],
            ]],
        ], 200),
    ]);
}

function addStoredSecurityAuditUnitImage(Unit $unit, string $filename = 'security-audit.jpg'): UnitImage
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

test('openai key is never exposed in rendered html or livewire payload', function () {
    Storage::fake('public');
    Http::preventStrayRequests();
    fakeOpenAiSecurityAuditSuccess();

    $secret = 'sk-live-super-secret-123456789';
    config([
        'services.openai.key' => $secret,
        'services.openai.model' => 'gpt-4.1-mini',
    ]);

    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create([
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    addStoredSecurityAuditUnitImage($unit);
    RateLimiter::clear('unit-ai-description:'.$admin->id);

    $component = Livewire::actingAs($admin)->test(UnitForm::class, ['unit' => $unit]);
    $initialHtml = $component->html();
    $initialSnapshot = json_encode($component->snapshot);

    expect($initialHtml)->not->toContain($secret);
    expect($initialSnapshot ?: '')->not->toContain($secret);

    $component->call('generateAiDescription')->assertHasNoErrors();

    $updatedHtml = $component->html();
    $updatedSnapshot = json_encode($component->snapshot);
    $updatedEffects = json_encode($component->effects);

    expect($updatedHtml)->not->toContain($secret);
    expect($updatedSnapshot ?: '')->not->toContain($secret);
    expect($updatedEffects ?: '')->not->toContain($secret);
});

test('ai generation does not persist base64 image content in unit fields or audit logs', function () {
    Storage::fake('public');
    Http::preventStrayRequests();
    fakeOpenAiSecurityAuditSuccess();
    config(['services.openai.key' => 'test-key', 'services.openai.model' => 'gpt-4.1-mini']);

    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create([
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    addStoredSecurityAuditUnitImage($unit);
    RateLimiter::clear('unit-ai-description:'.$admin->id);

    Livewire::actingAs($admin)
        ->test(UnitForm::class, ['unit' => $unit])
        ->call('generateAiDescription')
        ->assertHasNoErrors()
        ->call('save')
        ->assertHasNoErrors();

    $unit->refresh();
    $metaJson = json_encode($unit->ai_description_meta);
    $audit = AuditLog::query()
        ->where('action', 'AI_DESCRIPTION_GENERATED')
        ->where('unit_id', $unit->id)
        ->latest('id')
        ->firstOrFail();
    $auditChangesJson = json_encode($audit->changes);

    expect((string) $unit->ai_description_draft)->not->toContain('data:image/');
    expect((string) $unit->ai_description_draft)->not->toContain('base64,');
    expect($metaJson ?: '')->not->toContain('data:image/');
    expect($metaJson ?: '')->not->toContain('base64,');
    expect($auditChangesJson ?: '')->not->toContain('data:image/');
    expect($auditChangesJson ?: '')->not->toContain('base64,');
});

test('rate limiting is enforced per admin user identity', function () {
    Storage::fake('public');
    Http::preventStrayRequests();
    fakeOpenAiSecurityAuditSuccess();
    config(['services.openai.key' => 'test-key', 'services.openai.model' => 'gpt-4.1-mini']);

    $adminOne = User::factory()->admin()->create();
    $unitOne = Unit::factory()->create([
        'created_by' => $adminOne->id,
        'updated_by' => $adminOne->id,
    ]);
    addStoredSecurityAuditUnitImage($unitOne, 'first-admin.jpg');
    RateLimiter::clear('unit-ai-description:'.$adminOne->id);

    $componentOne = Livewire::actingAs($adminOne)->test(UnitForm::class, ['unit' => $unitOne]);
    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $componentOne->call('generateAiDescription')->assertHasNoErrors();
    }
    $componentOne->call('generateAiDescription')
        ->assertHasErrors(['ai_generation' => 'Too many attempts. Please try again in a minute.']);

    $adminTwo = User::factory()->admin()->create();
    $unitTwo = Unit::factory()->create([
        'created_by' => $adminTwo->id,
        'updated_by' => $adminTwo->id,
    ]);
    addStoredSecurityAuditUnitImage($unitTwo, 'second-admin.jpg');
    RateLimiter::clear('unit-ai-description:'.$adminTwo->id);

    Livewire::actingAs($adminTwo)
        ->test(UnitForm::class, ['unit' => $unitTwo])
        ->call('generateAiDescription')
        ->assertHasNoErrors();
});

test('only admins can access ai generation path', function () {
    Storage::fake('public');
    config(['services.openai.key' => 'test-key', 'services.openai.model' => 'gpt-4.1-mini']);

    $ownerAdmin = User::factory()->admin()->create();
    $unit = Unit::factory()->create([
        'created_by' => $ownerAdmin->id,
        'updated_by' => $ownerAdmin->id,
    ]);
    addStoredSecurityAuditUnitImage($unit);

    $nonAdmin = User::factory()->create(['is_admin' => false]);
    $this->actingAs($nonAdmin)
        ->get(route('admin.units.edit', $unit))
        ->assertForbidden();
});

test('provider errors are sanitized and not exposed to ui', function () {
    Storage::fake('public');
    Http::preventStrayRequests();
    config(['services.openai.key' => 'test-key', 'services.openai.model' => 'gpt-4.1-mini']);

    Http::fake([
        'https://api.openai.com/*' => Http::response([
            'error' => [
                'message' => 'provider dump: invalid request trace with secret=sk-live-leak-me',
                'type' => 'invalid_request_error',
            ],
        ], 500),
    ]);

    $admin = User::factory()->admin()->create();
    $unit = Unit::factory()->create([
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    addStoredSecurityAuditUnitImage($unit);
    RateLimiter::clear('unit-ai-description:'.$admin->id);

    $component = Livewire::actingAs($admin)
        ->test(UnitForm::class, ['unit' => $unit])
        ->call('generateAiDescription')
        ->assertHasErrors([
            'ai_generation' => 'Unable to generate AI description right now. Please try again.',
        ]);

    $errorMessage = $component->errors()->first('ai_generation');
    expect($errorMessage)->not->toContain('provider dump');
    expect($errorMessage)->not->toContain('sk-live-leak-me');
});
