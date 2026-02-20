<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromAuthenticatedUser
{
    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || (bool) $user->is_super_admin) {
            $this->clearTenantContext();

            return $next($request);
        }

        $tenant = Tenant::query()->find($user->tenant_id);
        if ($tenant === null || $tenant->is_disabled) {
            abort(404);
        }

        $trialExpired = $tenant->trial_ends_at !== null && $tenant->trial_ends_at->isPast();
        if ($trialExpired && $this->requiresActiveTrial($request)) {
            abort(402, 'Tenant trial has expired.');
        }

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
            $this->clearTenantContext();
        }
    }

    private function clearTenantContext(): void
    {
        $this->tenantManager->clear();
        URL::defaults([]);
        View::share('tenantName', null);
        View::share('tenantSlug', null);
        View::share('tenantTrialExpired', null);
    }

    private function requiresActiveTrial(Request $request): bool
    {
        if ($request->routeIs('admin.*') || $request->routeIs('settings.*')) {
            return true;
        }

        if ($request->routeIs('logout')) {
            return true;
        }

        if ($request->routeIs('password.*') || $request->routeIs('verification.*') || $request->routeIs('two-factor.*')) {
            return true;
        }

        return false;
    }
}
