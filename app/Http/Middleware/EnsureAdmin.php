<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->is_admin || $user->is_super_admin) {
            abort(403, 'Unauthorized action.');
        }

        $tenant = $this->tenantManager->current();
        if (! $tenant || (int) $user->tenant_id !== (int) $tenant->getKey()) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}

