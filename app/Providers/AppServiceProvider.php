<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Rental;
use App\Models\Unit;
use App\Models\User;
use App\Models\ViewingRequest;
use App\Policies\AuditLogPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\RentalPolicy;
use App\Policies\UnitPolicy;
use App\Policies\ViewingRequestPolicy;
use App\Support\Tenancy\CurrentTenant;
use App\Support\Tenancy\TenantManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentTenant::class);
        $this->app->singleton(TenantManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();

        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function configureAuthorization(): void
    {
        Gate::policy(Unit::class, UnitPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(ViewingRequest::class, ViewingRequestPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Rental::class, RentalPolicy::class);

        Gate::define('access-admin', function (User $user): bool {
            $tenant = app(TenantManager::class)->current();

            return $user->isTenantAdminFor($tenant);
        });

        Gate::define('view-admin-notifications', function (User $user): bool {
            $tenant = app(TenantManager::class)->current();

            return $user->isTenantAdminFor($tenant);
        });

        Gate::define('access-super-admin', fn (User $user): bool => (bool) $user->is_super_admin);
    }
}
