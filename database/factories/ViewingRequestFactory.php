<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Unit;
use App\Models\ViewingRequest;
use App\Support\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ViewingRequest>
 */
class ViewingRequestFactory extends Factory
{
    protected $model = ViewingRequest::class;

    public function definition(): array
    {
        $tenantId = app(TenantManager::class)->currentId();
        $start = fake()->dateTimeBetween('+1 day', '+14 days');
        $end = (clone $start)->modify('+1 hour');

        return [
            'tenant_id' => $tenantId ?? Tenant::factory(),
            'unit_id' => Unit::factory(),
            'requester_name' => fake()->name(),
            'requester_email' => fake()->safeEmail(),
            'requester_phone' => fake()->numerify('09#########'),
            'requested_start_at' => $start,
            'requested_end_at' => $end,
            'status' => ViewingRequest::STATUS_PENDING,
            'notes' => fake()->optional()->sentence(),
            'ip_address' => fake()->ipv4(),
        ];
    }
}
