<?php

use App\Http\Controllers\RenterAccessController;
use App\Http\Controllers\RenterTicketController;
use App\Http\Controllers\TenantMediaController;
use App\Livewire\Admin\Analytics\Index as AnalyticsIndex;
use App\Livewire\Admin\AuditLogs\Index as AuditLogsIndex;
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
use App\Livewire\Public\ContactPage;
use App\Livewire\Public\UnitShow;
use App\Livewire\Super\Tenants\Index as SuperTenantsIndex;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shared Assets
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

/*
|--------------------------------------------------------------------------
| Public Landing Pages
|--------------------------------------------------------------------------
*/
Route::view('/', 'pages.landing')->name('landing');
Route::view('/instructions', 'pages.instructions')->name('instructions');

// Fallback for stale GET-based logout links in production.
Route::get('/logout', function (Request $request): RedirectResponse {
    if (Auth::guard('web')->check()) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Public Tenant Showroom Routes
|--------------------------------------------------------------------------
*/
Route::prefix('t/{tenant:slug}')
    ->middleware('tenant')
    ->group(function (): void {
        // Legacy compatibility: old tenant-scoped login URLs now redirect to global /login.
        Route::get('/login', fn (): RedirectResponse => redirect()->route('login'))
            ->name('tenant.legacy.login');

        Route::get('/', ShowroomIndex::class)->name('home');
        Route::get('/contact', ContactPage::class)->name('contact');
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
        | Legacy Tenant Admin URLs (compatibility redirect)
        |--------------------------------------------------------------------------
        */
        Route::middleware(['auth', 'verified', 'admin'])
            ->prefix('admin')
            ->group(function (): void {
                Route::get('/', fn (): RedirectResponse => redirect()->route('admin.dashboard'));
                Route::get('/dashboard', fn (): RedirectResponse => redirect()->route('admin.dashboard'));
                Route::get('/units', fn (): RedirectResponse => redirect()->route('admin.units.index'));
                Route::get('/units/create', fn (): RedirectResponse => redirect()->route('admin.units.create'));
                Route::get('/units/{unit}', fn ($unit): RedirectResponse => redirect()->route('admin.units.edit', ['unit' => $unit]));
                Route::get('/categories', fn (): RedirectResponse => redirect()->route('admin.categories.index'));
                Route::get('/viewing-requests', fn (): RedirectResponse => redirect()->route('admin.viewing-requests.index'));
                Route::get('/rentals', fn (): RedirectResponse => redirect()->route('admin.rentals.index'));
                Route::get('/rentals/create', fn (): RedirectResponse => redirect()->route('admin.rentals.create'));
                Route::get('/rentals/{rental}/edit', fn ($rental): RedirectResponse => redirect()->route('admin.rentals.edit', ['rental' => $rental]));
                Route::get('/analytics', fn (): RedirectResponse => redirect()->route('admin.analytics.index'));
                Route::get('/logs', fn (): RedirectResponse => redirect()->route('admin.logs.index'));
            });
    });

/*
|--------------------------------------------------------------------------
| Tenant Admin Routes (global path, tenant context from authenticated user)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'tenant.auth', 'admin'])
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

Route::middleware(['auth', 'verified'])
    ->get('/dashboard', function (Request $request): RedirectResponse {
        $user = $request->user();

        if ($user?->is_super_admin) {
            return redirect()->route('super.tenants.index');
        }

        if ($user?->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        abort(403, 'Unauthorized action.');
    })
    ->name('dashboard');

Route::middleware('tenant.auth')->group(function (): void {
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

            return redirect()->route('login');
        })->name('logout');
    });
