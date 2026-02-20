<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $defaultTenantId = $this->createOrGetDefaultTenantId();
        $this->backfillTenantOwnedTables($defaultTenantId);
        $this->backfillUsers($defaultTenantId);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        $defaultTenantId = DB::table('tenants')
            ->where('slug', 'default')
            ->value('id');

        if (! is_numeric($defaultTenantId)) {
            return;
        }

        $tenantId = (int) $defaultTenantId;

        foreach ($this->tenantOwnedTables() as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            DB::table($table)
                ->where('tenant_id', $tenantId)
                ->update(['tenant_id' => null]);
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'tenant_id')) {
            $query = DB::table('users')->where('tenant_id', $tenantId);

            if (Schema::hasColumn('users', 'is_super_admin')) {
                $query->where('is_super_admin', false);
            }

            $query->update(['tenant_id' => null]);
        }

        if (! $this->hasTenantReferences($tenantId)) {
            DB::table('tenants')->where('id', $tenantId)->delete();
        }
    }

    private function createOrGetDefaultTenantId(): int
    {
        $existingId = DB::table('tenants')
            ->where('slug', 'default')
            ->value('id');

        if (is_numeric($existingId)) {
            return (int) $existingId;
        }

        $now = now();

        return (int) DB::table('tenants')->insertGetId([
            'name' => 'Default Tenant',
            'slug' => 'default',
            'is_disabled' => false,
            'trial_ends_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function backfillTenantOwnedTables(int $tenantId): void
    {
        foreach ($this->tenantOwnedTables() as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            DB::table($table)
                ->whereNull('tenant_id')
                ->update(['tenant_id' => $tenantId]);
        }
    }

    private function backfillUsers(int $tenantId): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'tenant_id')) {
            return;
        }

        $query = DB::table('users')->whereNull('tenant_id');

        // Keep super-admin accounts globally scoped.
        if (Schema::hasColumn('users', 'is_super_admin')) {
            $query->where('is_super_admin', false);
        }

        $query->update(['tenant_id' => $tenantId]);
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

    private function hasTenantReferences(int $tenantId): bool
    {
        $tablesToCheck = array_merge(['users'], $this->tenantOwnedTables());

        foreach ($tablesToCheck as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            if (DB::table($table)->where('tenant_id', $tenantId)->exists()) {
                return true;
            }
        }

        return false;
    }
};
