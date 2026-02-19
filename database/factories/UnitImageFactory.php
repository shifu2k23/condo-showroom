<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UnitImage;
use App\Support\Tenancy\TenantManager;
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
        $tenantId = app(TenantManager::class)->currentId();

        return [
            'tenant_id' => $tenantId ?? Tenant::factory(),
            'public_id' => (string) Str::ulid(),
            'unit_id' => Unit::factory(),
            'path' => 'tenants/'.fake()->numberBetween(1, 999).'/units/'.fake()->numberBetween(1, 999).'/'.fake()->uuid().'.jpg',
            'sort_order' => 0,
        ];
    }
}
