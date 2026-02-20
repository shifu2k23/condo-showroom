<?php

namespace Database\Factories;

use App\Models\Rental;
use App\Models\RenterSession;
use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RenterSession>
 */
class RenterSessionFactory extends Factory
{
    protected $model = RenterSession::class;

    public function definition(): array
    {
        $tenantId = app(TenantManager::class)->currentId();

        return [
            'tenant_id' => $tenantId ?? Tenant::factory(),
            'rental_id' => Rental::factory(),
            'token_hash' => hash('sha256', Str::random(80)),
            'expires_at' => now()->addHour(),
            'last_used_at' => now(),
        ];
    }
}
