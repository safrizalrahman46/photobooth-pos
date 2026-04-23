<?php

use App\Http\Controllers\Web\AdminAddOnController;
use App\Http\Controllers\Web\AdminAppSettingController;
use App\Http\Controllers\Web\AdminAuthController;
use App\Http\Controllers\Web\AdminBlackoutDateController;
use App\Http\Controllers\Web\AdminBookingController;
use App\Http\Controllers\Web\AdminBranchController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminDashboardDataController;
use App\Http\Controllers\Web\AdminDashboardReportController;
use App\Http\Controllers\Web\AdminDesignController;
use App\Http\Controllers\Web\AdminPackageController;
use App\Http\Controllers\Web\AdminPaymentController;
use App\Http\Controllers\Web\AdminPrinterSettingController;
use App\Http\Controllers\Web\AdminQueueController;
use App\Http\Controllers\Web\AdminSettingsController;
use App\Http\Controllers\Web\AdminTimeSlotController;
use App\Http\Controllers\Web\AdminUserController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\QueueBoardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/queue-board', [QueueBoardController::class, 'index'])->name('queue.board');
Route::redirect('/panel', '/admin');
Route::get('/panel/{path}', fn () => redirect('/admin'))->where('path', '.*');

Route::prefix('booking')->name('booking.')->group(function () {
    Route::get('/', [BookingController::class, 'create'])->name('create');
    Route::get('/availability', [BookingController::class, 'availability'])->name('availability');
    Route::get('/payment', [BookingController::class, 'payment'])->name('payment');
    Route::post('/payment', [BookingController::class, 'preparePayment'])->name('payment.prepare');
    Route::post('/', [BookingController::class, 'store'])->name('store');
    Route::get('/success/{booking:booking_code}', [BookingController::class, 'success'])->name('success');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.attempt');

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/admin-dashboard', fn () => redirect()->route('admin.dashboard'));
        Route::get('/packages', AdminDashboardController::class);
        Route::get('/add-ons', AdminDashboardController::class);
        Route::get('/design-catalogs', AdminDashboardController::class);
        Route::get('/users', AdminDashboardController::class);
        Route::get('/bookings', AdminDashboardController::class);
        Route::get('/queue-tickets', AdminDashboardController::class);
        Route::get('/transactions', AdminDashboardController::class);
        Route::get('/reports', AdminDashboardController::class);
        Route::get('/activity-logs', AdminDashboardController::class);
        Route::get('/settings', AdminDashboardController::class);
        Route::get('/branches', AdminDashboardController::class);
        Route::get('/time-slots', AdminDashboardController::class);
        Route::get('/blackout-dates', AdminDashboardController::class);
        Route::get('/printer-settings', AdminDashboardController::class);
        Route::get('/payments', AdminDashboardController::class);
        Route::get('/app-settings', AdminDashboardController::class);

        Route::get('/dashboard-data', AdminDashboardDataController::class)->name('dashboard.data');
        Route::get('/dashboard-report', AdminDashboardReportController::class)->name('dashboard.report');

        Route::get('/packages-data', [AdminPackageController::class, 'index'])->name('packages.data');
        Route::post('/packages', [AdminPackageController::class, 'store'])->name('packages.store');
        Route::put('/packages/{package}', [AdminPackageController::class, 'update']);
        Route::delete('/packages/{package}', [AdminPackageController::class, 'destroy']);

        Route::get('/add-ons-data', [AdminAddOnController::class, 'index'])->name('add-ons.data');
        Route::post('/add-ons', [AdminAddOnController::class, 'store'])->name('add-ons.store');
        Route::put('/add-ons/{addOn}', [AdminAddOnController::class, 'update']);
        Route::delete('/add-ons/{addOn}', [AdminAddOnController::class, 'destroy']);

        Route::get('/designs-data', [AdminDesignController::class, 'index'])->name('designs.data');
        Route::post('/designs', [AdminDesignController::class, 'store'])->name('designs.store');
        Route::put('/designs/{designCatalog}', [AdminDesignController::class, 'update']);
        Route::delete('/designs/{designCatalog}', [AdminDesignController::class, 'destroy']);

        Route::get('/users-data', [AdminUserController::class, 'index'])->name('users.data');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');

        Route::get('/queue-data', [AdminQueueController::class, 'index'])->name('queue.data');
        Route::post('/queue/call-next', [AdminQueueController::class, 'callNext'])->name('queue.call-next');
        Route::post('/queue/check-in', [AdminQueueController::class, 'checkIn'])->name('queue.check-in');
        Route::post('/queue/walk-in', [AdminQueueController::class, 'walkIn'])->name('queue.walk-in');
        Route::patch('/queue/{queueTicket}/status', [AdminQueueController::class, 'transition']);

        Route::get('/settings-data', [AdminSettingsController::class, 'index'])->name('settings.data');
        Route::put('/settings/default-branch', [AdminSettingsController::class, 'updateDefaultBranch'])->name('settings.default-branch');
        Route::post('/settings/branches', [AdminSettingsController::class, 'storeBranch'])->name('settings.branches.store');
        Route::put('/settings/branches/{branch}', [AdminSettingsController::class, 'updateBranch']);
        Route::delete('/settings/branches/{branch}', [AdminSettingsController::class, 'destroyBranch']);

        Route::get('/branches-data', [AdminBranchController::class, 'index'])->name('branches.data');
        Route::post('/branches', [AdminBranchController::class, 'store'])->name('branches.store');
        Route::put('/branches/{branch}', [AdminBranchController::class, 'update']);
        Route::delete('/branches/{branch}', [AdminBranchController::class, 'destroy']);

        Route::get('/time-slots-data', [AdminTimeSlotController::class, 'index'])->name('time-slots.data');
        Route::post('/time-slots', [AdminTimeSlotController::class, 'store'])->name('time-slots.store');
        Route::put('/time-slots/{timeSlot}', [AdminTimeSlotController::class, 'update']);
        Route::delete('/time-slots/{timeSlot}', [AdminTimeSlotController::class, 'destroy']);
        Route::post('/time-slots/generate', [AdminTimeSlotController::class, 'generate'])->name('time-slots.generate');
        Route::post('/time-slots/bulk-bookable', [AdminTimeSlotController::class, 'bulkBookable'])->name('time-slots.bulk-bookable');

        Route::get('/blackout-dates-data', [AdminBlackoutDateController::class, 'index'])->name('blackout-dates.data');
        Route::post('/blackout-dates', [AdminBlackoutDateController::class, 'store'])->name('blackout-dates.store');
        Route::put('/blackout-dates/{blackoutDate}', [AdminBlackoutDateController::class, 'update']);
        Route::delete('/blackout-dates/{blackoutDate}', [AdminBlackoutDateController::class, 'destroy']);

        Route::get('/printer-settings-data', [AdminPrinterSettingController::class, 'index'])->name('printer-settings.data');
        Route::post('/printer-settings', [AdminPrinterSettingController::class, 'store'])->name('printer-settings.store');
        Route::put('/printer-settings/{printerSetting}', [AdminPrinterSettingController::class, 'update']);
        Route::delete('/printer-settings/{printerSetting}', [AdminPrinterSettingController::class, 'destroy']);
        Route::patch('/printer-settings/{printerSetting}/default', [AdminPrinterSettingController::class, 'setDefault'])->name('printer-settings.set-default');

        Route::get('/payments-data', [AdminPaymentController::class, 'index'])->name('payments.data');
        Route::post('/payments/{transaction}/store', [AdminPaymentController::class, 'store'])->name('payments.store');

        Route::get('/app-settings-data', [AdminAppSettingController::class, 'index'])->name('app-settings.data');
        Route::put('/app-settings/{group}', [AdminAppSettingController::class, 'update'])->name('app-settings.update');

        Route::post('/bookings', [AdminBookingController::class, 'store'])->name('bookings.store');
        Route::put('/bookings/{booking}', [AdminBookingController::class, 'update']);
        Route::delete('/bookings/{booking}', [AdminBookingController::class, 'destroy']);
        Route::post('/bookings/{booking}/confirm', [AdminBookingController::class, 'confirm']);
        Route::post('/bookings/{booking}/confirm-payment', [AdminBookingController::class, 'confirmPayment']);
        Route::get('/bookings/{booking}/transfer-proof', [AdminBookingController::class, 'transferProof'])->name('bookings.transfer-proof');
    });
});
