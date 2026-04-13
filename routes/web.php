<?php

use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\AdminDashboardDataController;
use App\Http\Controllers\Web\LandingController;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::prefix('booking')->name('booking.')->group(function () {
    Route::get('/', [BookingController::class, 'create'])->name('create');
    Route::get('/availability', [BookingController::class, 'availability'])->name('availability');
    Route::get('/payment', [BookingController::class, 'payment'])->name('payment');
    Route::post('/payment', [BookingController::class, 'preparePayment'])->name('payment.prepare');
    Route::post('/', [BookingController::class, 'store'])->name('store');
    Route::get('/success/{booking:booking_code}', [BookingController::class, 'success'])->name('success');
});

Route::prefix('admin')->name('admin.')->middleware([FilamentAuthenticate::class])->group(function () {
    Route::get('/dashboard-data', AdminDashboardDataController::class)->name('dashboard.data');
});
