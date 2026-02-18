<?php

namespace App\Http\Middleware;

use App\Services\RenterAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRenterSessionIsActive
{
    public function __construct(
        private readonly RenterAccessService $renterAccess
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rental = $this->renterAccess->resolveRentalFromBrowserSession();

        if (! $rental) {
            $this->renterAccess->clearBrowserSession();

            return redirect()
                ->route('renter.access')
                ->with('status', 'Your renter session has expired. Please sign in again.');
        }

        $request->attributes->set('active_rental', $rental);

        return $next($request);
    }
}
