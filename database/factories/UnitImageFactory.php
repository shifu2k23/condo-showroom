<?php

namespace Database\Factories;

use App\Models\Unit;
use App\Models\UnitImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UnitImage>
 */
class UnitImageFactory extends Factory
{
    protected $model = UnitImage::class;

    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'path' => 'units/'.Str::ulid().'/'.fake()->uuid().'.jpg',
            'sort_order' => 0,
        ];
    }
}
