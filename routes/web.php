<?php

use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\QueueBoardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/queue-board', [QueueBoardController::class, 'index'])->name('queue.board');

Route::prefix('booking')->name('booking.')->group(function () {
    Route::get('/', [BookingController::class, 'create'])->name('create');
    Route::get('/availability', [BookingController::class, 'availability'])->name('availability');
    Route::get('/payment', [BookingController::class, 'payment'])->name('payment');
    Route::post('/payment', [BookingController::class, 'preparePayment'])->name('payment.prepare');
    Route::post('/', [BookingController::class, 'store'])->name('store');
    Route::get('/success/{booking:booking_code}', [BookingController::class, 'success'])->name('success');
});
