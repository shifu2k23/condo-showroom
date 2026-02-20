<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<array{name:string, slug:string}>
     */
    private array $defaultCategories = [
        ['name' => '1 Bedroom', 'slug' => '1-bedroom'],
        ['name' => '2 Bedroom', 'slug' => '2-bedroom'],
        ['name' => 'Studio', 'slug' => 'studio'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        $this->dropIndexIfExists('categories', 'categories_name_unique', isUnique: true);
        $this->dropIndexIfExists('categories', 'categories_slug_unique', isUnique: true);

        if (
            Schema::hasColumn('categories', 'tenant_id')
            && Schema::hasColumn('categories', 'name')
            && Schema::hasColumn('categories', 'slug')
        ) {
            $this->addUniqueIfMissing('categories', ['tenant_id', 'name'], 'categories_tenant_name_unique');
            $this->addUniqueIfMissing('categories', ['tenant_id', 'slug'], 'categories_tenant_slug_unique');
        }

        $this->seedDefaultsForAllTenants();
    }

    public function down(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        $this->dropIndexIfExists('categories', 'categories_tenant_name_unique', isUnique: true);
        $this->dropIndexIfExists('categories', 'categories_tenant_slug_unique', isUnique: true);
    }

    private function seedDefaultsForAllTenants(): void
    {
        if (
            ! Schema::hasTable('tenants')
            || ! Schema::hasColumn('categories', 'tenant_id')
            || ! Schema::hasColumn('categories', 'name')
            || ! Schema::hasColumn('categories', 'slug')
        ) {
            return;
        }

        $tenantIds = DB::table('tenants')->pluck('id')->all();
        if ($tenantIds === []) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($tenantIds as $tenantId) {
            foreach ($this->defaultCategories as $category) {
                $rows[] = [
                    'tenant_id' => (int) $tenantId,
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('categories')->insertOrIgnore($rows);
    }

    /**
     * @param  list<string>  $columns
     */
    private function addUniqueIfMissing(string $table, array $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $name): void {
                $tableBlueprint->unique($columns, $name);
            });
        } catch (\Throwable) {
            // Ignore duplicate index errors on partially-migrated environments.
        }
    }

    private function dropIndexIfExists(string $table, string $name, bool $isUnique = false): void
    {
        try {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($name, $isUnique): void {
                if ($isUnique) {
                    $tableBlueprint->dropUnique($name);

                    return;
                }

                $tableBlueprint->dropIndex($name);
            });
        } catch (\Throwable) {
            // Ignore missing index errors.
        }
    }
};

