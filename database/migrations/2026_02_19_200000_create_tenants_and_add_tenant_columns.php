<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('is_disabled')->default(false)->index();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamps();
            });
        }

        $this->addTenantColumn('users', nullOnDelete: true);
        $this->addTenantColumn('categories');
        $this->addTenantColumn('units');
        $this->addTenantColumn('unit_images');
        $this->addTenantColumn('viewing_requests');
        $this->addTenantColumn('rentals');
        $this->addTenantColumn('renter_sessions');
        $this->addTenantColumn('maintenance_tickets');
        $this->addTenantColumn('audit_logs');

        // Tenant-aware analytics/config tables are optional in older environments.
        $this->addTenantColumn('app_settings');
        $this->addTenantColumn('analytics_snapshots');

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_super_admin')->default(false)->index();
            });
        }

        if (Schema::hasTable('unit_images') && ! Schema::hasColumn('unit_images', 'public_id')) {
            Schema::table('unit_images', function (Blueprint $table): void {
                $table->ulid('public_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('unit_images') && Schema::hasColumn('unit_images', 'public_id')) {
            Schema::table('unit_images', function (Blueprint $table): void {
                $table->dropColumn('public_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex(['is_super_admin']);
                $table->dropColumn('is_super_admin');
            });
        }

        $this->dropTenantColumn('analytics_snapshots');
        $this->dropTenantColumn('app_settings');
        $this->dropTenantColumn('audit_logs');
        $this->dropTenantColumn('maintenance_tickets');
        $this->dropTenantColumn('renter_sessions');
        $this->dropTenantColumn('rentals');
        $this->dropTenantColumn('viewing_requests');
        $this->dropTenantColumn('unit_images');
        $this->dropTenantColumn('units');
        $this->dropTenantColumn('categories');
        $this->dropTenantColumn('users');

        if (Schema::hasTable('tenants')) {
            Schema::drop('tenants');
        }
    }

    private function addTenantColumn(string $table, bool $nullOnDelete = false): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'tenant_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($nullOnDelete): void {
            $column = $tableBlueprint->foreignId('tenant_id')->nullable();

            $column = $column->constrained('tenants');
            if ($nullOnDelete) {
                $column->nullOnDelete();
            } else {
                $column->cascadeOnDelete();
            }
            $tableBlueprint->index('tenant_id');
        });
    }

    private function dropTenantColumn(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint): void {
            $tableBlueprint->dropConstrainedForeignId('tenant_id');
        });
    }
};
