<?php

use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\AdminDashboardDataController;
use App\Http\Controllers\Web\AdminDashboardReportController;
use App\Http\Controllers\Web\LandingController;
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
    ->middleware('auth')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/dashboard-data', AdminDashboardDataController::class)->name('dashboard.data');
        Route::get('/dashboard-report', AdminDashboardReportController::class)->name('dashboard.report');
    });

Route::get('/admin/{path}', function (string $path) {
    return redirect()->to('/panel/' . ltrim($path, '/'));
})->where('path', '.*');
