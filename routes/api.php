<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\DesignCatalogController;
use App\Http\Controllers\Api\V1\PackageController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\QueueController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SlotAvailabilityController;
use App\Http\Controllers\Api\V1\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('/packages', [PackageController::class, 'index']);
    Route::get('/packages/{package}', [PackageController::class, 'show']);
    Route::get('/design-catalogs', [DesignCatalogController::class, 'index']);
    Route::get('/design-catalogs/{designCatalog}', [DesignCatalogController::class, 'show']);
    Route::post('/slots/availability', SlotAvailabilityController::class);
    Route::post('/bookings', [BookingController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        Route::get('/bookings', [BookingController::class, 'index']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);

        Route::get('/queue-tickets', [QueueController::class, 'index']);
        Route::post('/queue-tickets/check-in', [QueueController::class, 'checkIn']);
        Route::post('/queue-tickets/walk-in', [QueueController::class, 'walkIn']);
        Route::patch('/queue-tickets/{queueTicket}/status', [QueueController::class, 'transition']);
        Route::post('/queue-tickets/call-next', [QueueController::class, 'callNext']);

        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::post('/transactions', [TransactionController::class, 'store']);
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
        Route::post('/transactions/{transaction}/payments', [PaymentController::class, 'store']);

        Route::get('/reports/summary', [ReportController::class, 'summary']);
    });
});
