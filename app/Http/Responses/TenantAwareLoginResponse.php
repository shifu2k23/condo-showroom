<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;

class TenantAwareLoginResponse implements LoginResponse, TwoFactorLoginResponse
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if ($user?->is_super_admin) {
            return redirect()->intended(route('super.tenants.index'));
        }

        $tenantSlug = $user?->tenant?->slug;
        if (is_string($tenantSlug) && $tenantSlug !== '') {
            return redirect()->intended(route('admin.dashboard', ['tenant' => $tenantSlug]));
        }

        return redirect()->route('tenant.login.chooser');
    }
}
