<?php

namespace Database\Factories;

use App\Models\MaintenanceTicket;
use App\Models\Rental;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceTicket>
 */
class MaintenanceTicketFactory extends Factory
{
    protected $model = MaintenanceTicket::class;

    public function definition(): array
    {
        return [
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
