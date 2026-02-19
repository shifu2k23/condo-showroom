<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfPossible(
            table: 'units',
            columns: ['tenant_id', 'category_id', 'status', 'deleted_at'],
            name: 'units_tenant_category_status_deleted_idx',
        );

        $this->addIndexIfPossible(
            table: 'categories',
            columns: ['tenant_id', 'name'],
            name: 'categories_tenant_name_idx',
        );

        $this->addIndexIfPossible(
            table: 'unit_images',
            columns: ['tenant_id', 'unit_id'],
            name: 'unit_images_tenant_unit_idx',
        );
    }

    public function down(): void
    {
        $this->dropIndexIfExists('unit_images', 'unit_images_tenant_unit_idx');
        $this->dropIndexIfExists('categories', 'categories_tenant_name_idx');
        $this->dropIndexIfExists('units', 'units_tenant_category_status_deleted_idx');
    }

    /**
     * @param  list<string>  $columns
     */
    private function addIndexIfPossible(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        try {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $name): void {
                $tableBlueprint->index($columns, $name);
            });
        } catch (\Throwable) {
            // Ignore duplicate index errors in partial/legacy environments.
        }
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($name): void {
                $tableBlueprint->dropIndex($name);
            });
        } catch (\Throwable) {
            // Ignore missing index errors.
        }
    }
};
