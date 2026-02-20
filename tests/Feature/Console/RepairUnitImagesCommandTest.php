<?php

use App\Models\Unit;
use App\Models\UnitImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

test('repair unit images command backfills tenant_id from unit', function () {
    Storage::fake('local');

    $unit = Unit::factory()->create();
    $path = "tenants/{$unit->tenant_id}/units/{$unit->id}/".Str::uuid().'.jpg';
    Storage::disk('local')->put($path, 'image-bytes');

    $publicId = (string) Str::ulid();
    DB::table('unit_images')->insert([
        'tenant_id' => null,
        'public_id' => $publicId,
        'unit_id' => $unit->id,
        'path' => $path,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('media:repair-unit-images')
        ->assertSuccessful();

    $this->assertDatabaseHas('unit_images', [
        'public_id' => $publicId,
        'tenant_id' => $unit->tenant_id,
        'path' => $path,
    ]);
});

test('repair unit images command can prune records with missing files', function () {
    Storage::fake('local');

    $unit = Unit::factory()->create();
    $missingPath = "tenants/{$unit->tenant_id}/units/{$unit->id}/missing-".Str::uuid().'.jpg';

    $image = UnitImage::query()->create([
        'tenant_id' => $unit->tenant_id,
        'unit_id' => $unit->id,
        'path' => $missingPath,
        'sort_order' => 0,
    ]);

    $this->artisan('media:repair-unit-images --prune-missing')
        ->assertSuccessful();

    $this->assertDatabaseMissing('unit_images', [
        'id' => $image->id,
    ]);
});

