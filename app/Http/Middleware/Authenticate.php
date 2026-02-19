<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        $routeTenant = $request->route('tenant');
        if ($routeTenant instanceof Tenant) {
            return route('login', ['tenant' => $routeTenant->slug]);
        }

        if (is_string($routeTenant) && trim($routeTenant) !== '') {
            return route('login', ['tenant' => $routeTenant]);
        }

        if (Schema::hasTable('tenants')) {
            $defaultSlug = Tenant::query()
                ->where('is_disabled', false)
                ->orderBy('id')
                ->value('slug');

            if (is_string($defaultSlug) && $defaultSlug !== '') {
                return route('login', ['tenant' => $defaultSlug]);
            }
        }

        return route('tenant.login.chooser');
    }
}
