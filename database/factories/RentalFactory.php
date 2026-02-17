<?php

namespace Database\Factories;

use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    protected $model = Rental::class;

    public function definition(): array
    {
        $plainCode = 'ABCD-EFGH-JKLM';

        return [
            'unit_id' => Unit::factory(),
            'renter_name' => fake()->name(),
            'id_type' => 'PASSPORT',
            'id_last4' => fake()->numerify('####'),
            'public_code_hash' => Hash::make($plainCode),
            'public_code_last4' => 'JKLM',
            'status' => Rental::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subMinute(),
        ]);
    }
}
