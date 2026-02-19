<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->tenantOwnedTables() as $table) {
            $this->setTenantIdNullable($table, false);
        }
    }

    public function down(): void
    {
        foreach ($this->tenantOwnedTables() as $table) {
            $this->setTenantIdNullable($table, true);
        }
    }

    /**
     * @return list<string>
     */
    private function tenantOwnedTables(): array
    {
        return [
            'categories',
            'units',
            'unit_images',
            'viewing_requests',
            'rentals',
            'renter_sessions',
            'maintenance_tickets',
            'audit_logs',
            'app_settings',
            'analytics_snapshots',
        ];
    }

    private function setTenantIdNullable(string $table, bool $nullable): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite cannot reliably alter column nullability in place.
            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $nullSql = $nullable ? 'NULL' : 'NOT NULL';
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `tenant_id` BIGINT UNSIGNED %s',
                $table,
                $nullSql
            ));

            return;
        }

        if ($driver === 'pgsql') {
            $action = $nullable ? 'DROP NOT NULL' : 'SET NOT NULL';
            DB::statement(sprintf(
                'ALTER TABLE "%s" ALTER COLUMN "tenant_id" %s',
                $table,
                $action
            ));

            return;
        }

        if ($driver === 'sqlsrv') {
            $nullSql = $nullable ? 'NULL' : 'NOT NULL';
            DB::statement(sprintf(
                'ALTER TABLE [%s] ALTER COLUMN [tenant_id] BIGINT %s',
                $table,
                $nullSql
            ));
        }
    }
};
