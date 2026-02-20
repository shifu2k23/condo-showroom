<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $tenantId = app(TenantManager::class)->currentId();
        $name = fake()->unique()->words(2, true);

        return [
            'tenant_id' => $tenantId ?? Tenant::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
        ];
    }
}
