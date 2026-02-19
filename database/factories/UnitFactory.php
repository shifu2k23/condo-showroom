<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Support\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $tenantId = app(TenantManager::class)->currentId();
        $name = fake()->unique()->words(3, true);

        return [
            'tenant_id' => $tenantId ?? Tenant::factory(),
            'public_id' => (string) Str::ulid(),
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'location' => fake()->city(),
            'latitude' => '14.5995000',
            'longitude' => '120.9842000',
            'address_text' => fake()->streetAddress(),
            'description' => fake()->paragraph(),
            'status' => Unit::STATUS_AVAILABLE,
            'nightly_price_php' => fake()->numberBetween(1200, 8000),
            'monthly_price_php' => fake()->numberBetween(28000, 150000),
            'price_display_mode' => Unit::DISPLAY_NIGHT,
            'estimator_mode' => Unit::ESTIMATOR_HYBRID,
            'allow_estimator' => true,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Unit::STATUS_UNAVAILABLE,
        ]);
    }
}
