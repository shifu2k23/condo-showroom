<?php

namespace App\Support\Tenancy;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TenantCategoryDefaults
{
    /**
     * @var list<string>
     */
    public const DEFAULT_CATEGORY_NAMES = [
        '1 Bedroom',
        '2 Bedroom',
        'Studio',
    ];

    public function seedForTenant(Tenant|int $tenant): void
    {
        if (! $this->canSeed()) {
            return;
        }

        $tenantId = $tenant instanceof Tenant ? (int) $tenant->id : (int) $tenant;
        if ($tenantId <= 0) {
            return;
        }

        foreach (self::DEFAULT_CATEGORY_NAMES as $name) {
            Category::query()
                ->withoutGlobalScope('tenant')
                ->firstOrCreate(
                    ['tenant_id' => $tenantId, 'name' => $name],
                    ['slug' => Str::slug($name)]
                );
        }
    }

    public function seedForAllTenants(): void
    {
        if (! $this->canSeed()) {
            return;
        }

        Tenant::query()
            ->select('id')
            ->chunkById(200, function ($tenants): void {
                foreach ($tenants as $tenant) {
                    $this->seedForTenant((int) $tenant->id);
                }
            });
    }

    private function canSeed(): bool
    {
        return Schema::hasTable('tenants')
            && Schema::hasTable('categories')
            && Schema::hasColumn('categories', 'tenant_id')
            && Schema::hasColumn('categories', 'name')
            && Schema::hasColumn('categories', 'slug');
    }
}

