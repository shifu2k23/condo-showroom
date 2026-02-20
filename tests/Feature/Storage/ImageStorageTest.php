<?php

use App\Models\Unit;
use App\Models\UnitImage;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('image service stores relative image paths only', function () {
    Storage::fake('local');

    $unit = Unit::factory()->create();
    $file = UploadedFile::fake()->image('cover.jpg');

    $path = app(ImageService::class)->storeUnitImage($file, $unit->id);

    UnitImage::create([
        'tenant_id' => $unit->tenant_id,
        'unit_id' => $unit->id,
        'path' => $path,
        'sort_order' => 0,
    ]);

    expect($path)->toStartWith("tenants/{$unit->tenant_id}/units/{$unit->id}/");
    expect($path)->not->toStartWith('http');

    $this->assertDatabaseHas('unit_images', [
        'unit_id' => $unit->id,
        'path' => $path,
    ]);
});
