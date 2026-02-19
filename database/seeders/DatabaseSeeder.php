<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultTenant = Tenant::query()->firstOrCreate(
            ['slug' => 'default'],
            ['name' => 'Default Tenant', 'is_disabled' => false],
        );

        $this->call([
            CategorySeeder::class,
        ]);

        User::updateOrCreate(
            ['email' => 'benregidor@example.com'],
            [
                'tenant_id' => $defaultTenant->id,
                'name' => 'benregidor',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_super_admin' => false,
            ]
        );

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'tenant_id' => $defaultTenant->id,
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_super_admin' => false,
            ]
        );

        User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'tenant_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin123'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_super_admin' => true,
            ]
        );
    }
}
