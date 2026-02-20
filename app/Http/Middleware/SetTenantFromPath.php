<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromPath
{
    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request->route('tenant'));
        if ($tenant === null || $tenant->is_disabled) {
            abort(404);
        }

        $trialExpired = $tenant->trial_ends_at !== null && $tenant->trial_ends_at->isPast();
        if ($trialExpired && $this->requiresActiveTrial($request)) {
            abort(402, 'Tenant trial has expired.');
        }

        $request->route()?->setParameter('tenant', $tenant);
        $this->tenantManager->setCurrent($tenant);
        URL::defaults([
            'tenant' => $tenant->slug,
            'tenant:slug' => $tenant->slug,
        ]);
        View::share('tenantName', $tenant->name);
        View::share('tenantSlug', $tenant->slug);
        View::share('tenantTrialExpired', $trialExpired);

        try {
            return $next($request);
        } finally {
            $this->tenantManager->clear();
            URL::defaults([]);
            View::share('tenantName', null);
            View::share('tenantSlug', null);
            View::share('tenantTrialExpired', null);
        }
    }

    private function resolveTenant(mixed $routeTenant): ?Tenant
    {
        if ($routeTenant instanceof Tenant) {
            return $routeTenant;
        }

        if (! is_string($routeTenant) || trim($routeTenant) === '') {
            return null;
        }

        return Tenant::query()
            ->where('slug', $routeTenant)
            ->first();
    }

    private function requiresActiveTrial(Request $request): bool
    {
        if ($request->routeIs('admin.*') || $request->routeIs('settings.*')) {
            return true;
        }

        if ($request->routeIs('login') || $request->routeIs('logout')) {
            return true;
        }

        if ($request->routeIs('password.*') || $request->routeIs('verification.*')) {
            return true;
        }

        return false;
    }
}
