<?php

use App\Livewire\Admin\AuditLogs\Index as AuditLogsIndex;
use App\Livewire\Admin\Categories\Index as CategoriesIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Rentals\Form as RentalForm;
use App\Livewire\Admin\Rentals\Index as RentalsIndex;
use App\Livewire\Admin\Units\Form as UnitForm;
use App\Livewire\Admin\Units\Index as UnitsIndex;
use App\Livewire\Admin\ViewingRequests\Index as ViewingRequestsIndex;
use App\Livewire\Public\RenterPortal;
use App\Livewire\Public\ShowroomIndex;
use App\Livewire\Public\UnitShow;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Showroom Routes
|--------------------------------------------------------------------------
*/
Route::get('/', ShowroomIndex::class)->name('home');
Route::get('/units/{unit}', UnitShow::class)->name('unit.show');
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
