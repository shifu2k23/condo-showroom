<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class TenancyBootstrapDefault extends Command
{
    private const DEFAULT_INITIAL_ADMIN_PASSWORD = '12345678';

    protected $signature = 'tenancy:bootstrap-default
        {--slug=default : Default tenant slug}
        {--name=Default Tenant : Default tenant display name}
        {--admin-email=admin@default.local : Default tenant admin email}
        {--admin-name=Default Tenant Admin : Default tenant admin name}
        {--dry-run : Print intended mutations without updating rows}';

    protected $description = 'Create/use the default tenant and backfill nullable tenant_id columns.';

    public function handle(): int
    {
        if (! Schema::hasTable('tenants')) {
            $this->error('Tenants table does not exist. Run migrations first.');

            return self::FAILURE;
        }

        $tenant = $this->createOrGetDefaultTenant(
            slug: (string) $this->option('slug'),
            name: (string) $this->option('name'),
        );

        $this->info("Using tenant #{$tenant->id} ({$tenant->slug}).");

        $dryRun = (bool) $this->option('dry-run');

        $this->backfillTenantOwnedTables((int) $tenant->id, $dryRun);
        $this->backfillUsers((int) $tenant->id, $dryRun);
        $this->ensureDefaultTenantAdmin($tenant, $dryRun);

        $this->info($dryRun ? 'Dry run completed.' : 'Bootstrap backfill completed.');

        return self::SUCCESS;
    }

    private function createOrGetDefaultTenant(string $slug, string $name): Tenant
    {
        return Tenant::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name !== '' ? $name : 'Default Tenant',
                'is_disabled' => false,
                'trial_ends_at' => null,
            ],
        );
    }

    private function backfillTenantOwnedTables(int $tenantId, bool $dryRun): void
    {
        foreach ($this->tenantOwnedTables() as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            $count = DB::table($table)->whereNull('tenant_id')->count();
            if ($count === 0) {
                continue;
            }

            if ($dryRun) {
                $this->line("[dry-run] {$table}: {$count} rows would be backfilled.");
                continue;
            }

            DB::table($table)
                ->whereNull('tenant_id')
                ->update(['tenant_id' => $tenantId]);

            $this->line("{$table}: backfilled {$count} rows.");
        }
    }

    private function backfillUsers(int $tenantId, bool $dryRun): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'tenant_id')) {
            return;
        }

        $query = DB::table('users')->whereNull('tenant_id');

        if (Schema::hasColumn('users', 'is_super_admin')) {
            $query->where('is_super_admin', false);
        }

        $count = (clone $query)->count();
        if ($count === 0) {
            return;
        }

        if ($dryRun) {
            $this->line("[dry-run] users: {$count} rows would be backfilled.");

            return;
        }

        $query->update(['tenant_id' => $tenantId]);
        $this->line("users: backfilled {$count} rows.");
    }

    private function ensureDefaultTenantAdmin(Tenant $tenant, bool $dryRun): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $existingAdmin = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', true)
            ->where('is_super_admin', false)
            ->exists();

        if ($existingAdmin) {
            $this->line('Default tenant already has an admin user.');

            return;
        }

        $email = strtolower(trim((string) $this->option('admin-email')));
        $name = trim((string) $this->option('admin-name'));
        $password = self::DEFAULT_INITIAL_ADMIN_PASSWORD;

        if ($dryRun) {
            $this->line("[dry-run] Would create default tenant admin {$email}.");

            return;
        }

        User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $name !== '' ? $name : 'Default Tenant Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_admin' => true,
            'is_super_admin' => false,
        ]);

        $this->warn("Created default tenant admin: {$email}");
        $this->warn("Default password: {$password}");
        $this->warn('Ask the admin to change it immediately after first login.');
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
}
