<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = Tenant::query()->where('slug', 'default')->value('id');
        if (! is_numeric($tenantId)) {
            $tenantId = Tenant::query()->value('id');
        }

        if (! is_numeric($tenantId)) {
            return;
        }

        $defaults = [
            '1 Bedroom',
            '2 Bedroom',
            'Studio',
        ];

        foreach ($defaults as $name) {
            Category::query()->withoutGlobalScope('tenant')->firstOrCreate(
                ['tenant_id' => (int) $tenantId, 'name' => $name],
                ['slug' => Str::slug($name)]
            );
        }
    }
}
