<?php

use App\Http\Controllers\RenterAccessController;
use App\Http\Controllers\RenterTicketController;
use App\Http\Controllers\TenantMediaController;
use App\Livewire\Admin\AuditLogs\Index as AuditLogsIndex;
use App\Livewire\Admin\Analytics\Index as AnalyticsIndex;
use App\Livewire\Admin\Categories\Index as CategoriesIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Rentals\Form as RentalForm;
use App\Livewire\Admin\Rentals\Index as RentalsIndex;
use App\Livewire\Admin\Units\Form as UnitForm;
use App\Livewire\Admin\Units\Index as UnitsIndex;
use App\Livewire\Admin\ViewingRequests\Index as ViewingRequestsIndex;
use App\Livewire\Public\RenterDashboard;
use App\Livewire\Public\RenterPortal;
use App\Livewire\Public\RenterTickets;
use App\Livewire\Public\ShowroomIndex;
use App\Livewire\Public\UnitShow;
use App\Livewire\Super\Tenants\Index as SuperTenantsIndex;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Showroom Routes
|--------------------------------------------------------------------------
*/
Route::get('/project-images/{filename}', function (string $filename) {
    abort_unless(
        preg_match('/^[A-Za-z0-9._-]+\.(jpg|jpeg|png|webp|avif)$/i', $filename) === 1,
        404
    );

    $path = base_path('images'.DIRECTORY_SEPARATOR.$filename);

    abort_unless(is_file($path), 404);

    return response()->file($path, [
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('project.image');

Route::get('/', fn () => redirect()->route('tenant.login.chooser'));

Route::get('/login', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->is_super_admin) {
            return redirect()->route('super.tenants.index');
        }

        if ($user->tenant?->slug !== null) {
            return redirect()->route('admin.dashboard', ['tenant' => $user->tenant->slug]);
        }
    }

    return view('pages::auth.tenant-login-chooser');
})->name('tenant.login.chooser');

Route::post('/login', function (Request $request): RedirectResponse {
    $validated = $request->validate([
        'tenant_slug' => ['required', 'string', 'alpha_dash', 'max:80'],
    ]);

    $slug = strtolower(trim($validated['tenant_slug']));

    $tenantExists = Tenant::query()
        ->where('slug', $slug)
        ->where('is_disabled', false)
        ->exists();

    abort_unless($tenantExists, 404);

    return redirect()->route('login', ['tenant' => $slug]);
})->name('tenant.login.redirect');

/*
|--------------------------------------------------------------------------
| Legacy Admin URL Compatibility
|--------------------------------------------------------------------------
*/
$legacyAdminRedirect = function (Request $request, string $routeName, array $parameters = []): RedirectResponse {
    $user = $request->user();

    if ($user?->is_super_admin) {
        return redirect()->route('super.tenants.index');
    }

    abort_unless(
        $user !== null
        && (bool) $user->is_admin
        && is_string($user->tenant?->slug)
        && $user->tenant->slug !== '',
        403
    );

    return redirect()->route($routeName, array_merge(['tenant' => $user->tenant->slug], $parameters));
};

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->group(function () use ($legacyAdminRedirect): void {
        Route::get('/', fn (Request $request): RedirectResponse => $legacyAdminRedirect($request, 'admin.dashboard'));
        Route::get('/dashboard', fn (Request $request): RedirectResponse => $legacyAdminRedirect($request, 'admin.dashboard'));
        Route::get('/units', fn (Request $request): RedirectResponse => $legacyAdminRedirect($request, 'admin.units.index'));
        Route::get('/units/create', fn (Request $request): RedirectResponse => $legacyAdminRedirect($request, 'admin.units.create'));
        Route::get('/units/{unit}/edit', fn (Request $request, string $unit): RedirectResponse => $legacyAdminRedirect($request, 'admin.units.edit', ['unit' => $unit]));
        Route::get('/categories', fn (Request $request): RedirectResponse => $legacyAdminRedirect($request, 'admin.categories.index'));
        Route::get('/viewing-requests', fn (Request $request): RedirectResponse => $legacyAdminRedirect($request, 'admin.viewing-requests.index'));
        Route::get('/rentals', fn (Request $request): RedirectResponse => $legacyAdminRedirect($request, 'admin.rentals.index'));
        Route::get('/rentals/create', fn (Request $request): RedirectResponse => $legacyAdminRedirect($request, 'admin.rentals.create'));
        Route::get('/rentals/{rental}/edit', fn (Request $request, string $rental): RedirectResponse => $legacyAdminRedirect($request, 'admin.rentals.edit', ['rental' => $rental]));
        Route::get('/analytics', fn (Request $request): RedirectResponse => $legacyAdminRedirect($request, 'admin.analytics.index'));
        Route::get('/logs', fn (Request $request): RedirectResponse => $legacyAdminRedirect($request, 'admin.logs.index'));
    });

Route::prefix('t/{tenant:slug}')
    ->middleware('tenant')
    ->group(function (): void {
        Route::get('/', ShowroomIndex::class)->name('home');
        Route::get('/units/{unit:public_id}', UnitShow::class)->name('unit.show');
        Route::get('/renter/access', RenterPortal::class)->middleware('no-store')->name('renter.access');
        Route::post('/renter/access', [RenterAccessController::class, 'store'])->middleware('no-store')->name('renter.access.store');
        Route::get('/renter/dashboard', RenterDashboard::class)
            ->middleware(['no-store', 'renter.session.active'])
            ->name('renter.dashboard');
        Route::get('/renter/tickets', RenterTickets::class)
            ->middleware(['no-store', 'renter.session.active'])
            ->name('renter.tickets');
        Route::post('/renter/tickets', [RenterTicketController::class, 'store'])
            ->middleware(['no-store', 'renter.session.active'])
            ->name('renter.tickets.store');
        Route::get('/renter', RenterPortal::class)->middleware('no-store')->name('renter.portal');

        Route::get('/media/unit-images/{unitImage:public_id}', [TenantMediaController::class, 'showUnitImage'])
            ->name('tenant.media.unit-images.show');

        /*
        |--------------------------------------------------------------------------
        | Hidden Admin Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(['auth', 'verified', 'admin'])
            ->prefix('admin')
            ->name('admin.')
            ->group(function (): void {
                Route::get('/', Dashboard::class)->name('dashboard');

                Route::get('/units', UnitsIndex::class)->name('units.index');
                Route::get('/units/create', UnitForm::class)->name('units.create');
                Route::get('/units/{unit}/edit', UnitForm::class)->name('units.edit');

                Route::get('/categories', CategoriesIndex::class)->name('categories.index');
                Route::get('/viewing-requests', ViewingRequestsIndex::class)->name('viewing-requests.index');
                Route::get('/rentals', RentalsIndex::class)->name('rentals.index');
                Route::get('/rentals/create', RentalForm::class)->name('rentals.create');
                Route::get('/rentals/{rental}/edit', RentalForm::class)->name('rentals.edit');
                Route::get('/analytics', AnalyticsIndex::class)->name('analytics.index');
                Route::get('/logs', AuditLogsIndex::class)->name('logs.index');
            });

        /*
        |--------------------------------------------------------------------------
        | Compatibility Route
        |--------------------------------------------------------------------------
        */
        Route::middleware(['auth', 'verified', 'admin'])
            ->get('/dashboard', fn () => redirect()->route('admin.dashboard'))
            ->name('dashboard');

        require __DIR__.'/settings.php';
    });

Route::middleware(['tenancy.disabled', 'auth', 'verified', 'super.admin'])
    ->prefix('super')
    ->name('super.')
    ->group(function (): void {
        Route::get('/tenants', SuperTenantsIndex::class)->name('tenants.index');
        Route::post('/logout', function (Request $request): RedirectResponse {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tenant.login.chooser');
        })->name('logout');
    });
