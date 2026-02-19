<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse;

class TenantAwareVerifyEmailResponse implements VerifyEmailResponse
{
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();

        if ($user?->is_super_admin) {
            return redirect()->intended(route('super.tenants.index').'?verified=1');
        }

        return redirect()->intended(route('admin.dashboard').'?verified=1');
    }
}
