<?php

use App\Http\Controllers\RenterAccessController;
use App\Http\Controllers\RenterTicketController;
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

Route::get('/', ShowroomIndex::class)->name('home');
Route::get('/units/{unit}', UnitShow::class)->name('unit.show');
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
