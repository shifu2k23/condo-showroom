<?php

namespace Database\Seeders;

use App\Support\Tenancy\TenantCategoryDefaults;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(TenantCategoryDefaults::class)->seedForAllTenants();
    }
}
