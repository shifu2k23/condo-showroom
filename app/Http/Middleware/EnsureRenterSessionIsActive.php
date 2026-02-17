<?php

namespace App\Http\Middleware;

use App\Models\Rental;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRenterSessionIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session()->get('renter_access');

        if (! is_array($session) || ! isset($session['rental_id'], $session['expires_at'])) {
            return redirect()->route('renter.portal');
        }

        $expiresAt = CarbonImmutable::parse((string) $session['expires_at']);
        if (CarbonImmutable::now()->gte($expiresAt)) {
            $request->session()->forget('renter_access');

            return redirect()
                ->route('renter.portal')
                ->with('status', 'Your renter session has expired. Please sign in again.');
        }

        $rental = Rental::query()->find((int) $session['rental_id']);
        if (! $rental || $rental->status !== Rental::STATUS_ACTIVE) {
            $request->session()->forget('renter_access');

            return redirect()
                ->route('renter.portal')
                ->with('status', 'Your rental access is no longer active.');
        }

        $now = CarbonImmutable::now();
        if ($now->lt(CarbonImmutable::instance($rental->starts_at)) || $now->gt(CarbonImmutable::instance($rental->ends_at))) {
            $request->session()->forget('renter_access');

            return redirect()
                ->route('renter.portal')
                ->with('status', 'Your rental access is no longer active.');
        }

        return $next($request);
    }
}
