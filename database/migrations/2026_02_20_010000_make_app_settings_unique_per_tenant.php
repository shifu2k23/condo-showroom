<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        // Legacy schema had a global unique index on `key`, which blocks
        // multiple tenants from storing the same setting keys.
        $this->dropUniqueIfExists('app_settings', 'app_settings_key_unique');

        if (! Schema::hasColumn('app_settings', 'tenant_id') || ! Schema::hasColumn('app_settings', 'key')) {
            return;
        }

        $this->addUniqueIfPossible(
            table: 'app_settings',
            columns: ['tenant_id', 'key'],
            name: 'app_settings_tenant_key_unique',
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        $this->dropUniqueIfExists('app_settings', 'app_settings_tenant_key_unique');

        if (! Schema::hasColumn('app_settings', 'key')) {
            return;
        }

        $this->addUniqueIfPossible(
            table: 'app_settings',
            columns: ['key'],
            name: 'app_settings_key_unique',
        );
    }

    /**
     * @param  list<string>  $columns
     */
    private function addUniqueIfPossible(string $table, array $columns, string $name): void
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        try {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $name): void {
                $tableBlueprint->unique($columns, $name);
            });
        } catch (\Throwable) {
            // Ignore duplicate index creation / incompatible rollback states.
        }
    }

    private function dropUniqueIfExists(string $table, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($name): void {
                $tableBlueprint->dropUnique($name);
            });
        } catch (\Throwable) {
            // Ignore missing index names across environments.
        }
    }
};

