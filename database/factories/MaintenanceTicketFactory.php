<?php

namespace Database\Factories;

use App\Models\MaintenanceTicket;
use App\Models\Rental;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceTicket>
 */
class MaintenanceTicketFactory extends Factory
{
    protected $model = MaintenanceTicket::class;

    public function definition(): array
    {
        $tenantId = app(TenantManager::class)->currentId();

        return [
            'tenant_id' => $tenantId ?? Tenant::factory(),
            'rental_id' => Rental::factory(),
            'unit_id' => Unit::factory(),
            'status' => MaintenanceTicket::STATUS_OPEN,
            'category' => fake()->randomElement([
                MaintenanceTicket::CATEGORY_CLEANING,
                MaintenanceTicket::CATEGORY_PLUMBING,
                MaintenanceTicket::CATEGORY_ELECTRICAL,
                MaintenanceTicket::CATEGORY_OTHER,
            ]),
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'attachment_path' => null,
        ];
    }
}
