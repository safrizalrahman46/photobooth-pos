<?php

use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\AdminDashboardDataController;
use App\Http\Controllers\Web\AdminDashboardReportController;
use App\Http\Controllers\Web\AdminAuthController;
use App\Http\Controllers\Web\AdminBookingController;
use App\Http\Controllers\Web\AdminAddOnController;
use App\Http\Controllers\Web\AdminDesignController;
use App\Http\Controllers\Web\AdminPackageController;
use App\Http\Controllers\Web\AdminQueueController;
use App\Http\Controllers\Web\AdminSettingsController;
use App\Http\Controllers\Web\AdminUserController;
use App\Http\Controllers\Web\LandingController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::prefix('booking')->name('booking.')->group(function () {
    Route::get('/data-pemesan', [BookingController::class, 'customer'])->name('customer');
    Route::post('/data-pemesan', [BookingController::class, 'storeCustomer'])->name('customer.store');
    Route::get('/', [BookingController::class, 'create'])->name('create');
    Route::get('/availability', [BookingController::class, 'availability'])->name('availability');
    Route::get('/payment', [BookingController::class, 'payment'])->name('payment');
    Route::post('/payment', [BookingController::class, 'preparePayment'])->name('payment.prepare');
    Route::post('/', [BookingController::class, 'store'])->name('store');
    Route::get('/success/{booking:booking_code}', [BookingController::class, 'success'])->name('success');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware('guest')
    ->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.attempt');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware('auth')
    ->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/dashboard-data', AdminDashboardDataController::class)->name('dashboard.data');
        Route::get('/dashboard-report', AdminDashboardReportController::class)->name('dashboard.report');
        Route::get('/packages-data', [AdminPackageController::class, 'index'])->name('packages.data');
        Route::post('/packages', [AdminPackageController::class, 'store'])->name('packages.store');
        Route::put('/packages/{package}', [AdminPackageController::class, 'update'])->name('packages.update');
        Route::delete('/packages/{package}', [AdminPackageController::class, 'destroy'])->name('packages.destroy');
        Route::get('/add-ons-data', [AdminAddOnController::class, 'index'])->name('add-ons.data');
        Route::post('/add-ons', [AdminAddOnController::class, 'store'])->name('add-ons.store');
        Route::put('/add-ons/{addOn}', [AdminAddOnController::class, 'update'])->name('add-ons.update');
        Route::delete('/add-ons/{addOn}', [AdminAddOnController::class, 'destroy'])->name('add-ons.destroy');
        Route::get('/designs-data', [AdminDesignController::class, 'index'])->name('designs.data');
        Route::post('/designs', [AdminDesignController::class, 'store'])->name('designs.store');
        Route::put('/designs/{designCatalog}', [AdminDesignController::class, 'update'])->name('designs.update');
        Route::delete('/designs/{designCatalog}', [AdminDesignController::class, 'destroy'])->name('designs.destroy');
        Route::get('/users-data', [AdminUserController::class, 'index'])->name('users.data');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/queue-data', [AdminQueueController::class, 'index'])->name('queue.data');
        Route::post('/queue/call-next', [AdminQueueController::class, 'callNext'])->name('queue.call-next');
        Route::post('/queue/check-in', [AdminQueueController::class, 'checkIn'])->name('queue.check-in');
        Route::post('/queue/walk-in', [AdminQueueController::class, 'walkIn'])->name('queue.walk-in');
        Route::patch('/queue/{queueTicket}/status', [AdminQueueController::class, 'transition'])->name('queue.transition');
        Route::get('/settings-data', [AdminSettingsController::class, 'index'])->name('settings.data');
        Route::put('/settings/default-branch', [AdminSettingsController::class, 'updateDefaultBranch'])->name('settings.default-branch');
        Route::post('/settings/branches', [AdminSettingsController::class, 'storeBranch'])->name('settings.branches.store');
        Route::put('/settings/branches/{branch}', [AdminSettingsController::class, 'updateBranch'])->name('settings.branches.update');
        Route::delete('/settings/branches/{branch}', [AdminSettingsController::class, 'destroyBranch'])->name('settings.branches.destroy');
        Route::post('/bookings', [AdminBookingController::class, 'store'])->name('bookings.store');
        Route::put('/bookings/{booking}', [AdminBookingController::class, 'update'])->name('bookings.update');
        Route::delete('/bookings/{booking}', [AdminBookingController::class, 'destroy'])->name('bookings.destroy');
        Route::post('/bookings/{booking}/confirm', [AdminBookingController::class, 'confirm'])->name('bookings.confirm');
        Route::post('/bookings/{booking}/confirm-payment', [AdminBookingController::class, 'confirmPayment'])->name('bookings.confirm-payment');
        Route::get('/{path}', AdminDashboardController::class)
            ->where('path', '.*')
            ->name('dashboard.custom');
    });

Route::get('/login', function () {
    return redirect()->route('admin.login');
});

Route::get('/panel/{path?}', function () {
    return redirect()->route(Auth::check() ? 'admin.dashboard' : 'admin.login');
})->where('path', '.*');
