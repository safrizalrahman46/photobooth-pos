<?php

use App\Http\Controllers\Web\AdminAddOnController;
use App\Http\Controllers\Web\AdminAppSettingController;
use App\Http\Controllers\Web\AdminAuthController;
use App\Http\Controllers\Web\AdminBlackoutDateController;
use App\Http\Controllers\Web\AdminBookingController;
use App\Http\Controllers\Web\AdminBranchController;
use App\Http\Controllers\Web\AdminCashierSettlementController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminDashboardDataController;
use App\Http\Controllers\Web\AdminDashboardReportController;
use App\Http\Controllers\Web\AdminDesignController;
use App\Http\Controllers\Web\AdminInventoryController;
use App\Http\Controllers\Web\AdminPackageController;
use App\Http\Controllers\Web\AdminPaymentController;
use App\Http\Controllers\Web\AdminPrinterSettingController;
use App\Http\Controllers\Web\AdminQueueController;
use App\Http\Controllers\Web\AdminReferralController;
use App\Http\Controllers\Web\AdminSettingsController;
use App\Http\Controllers\Web\AdminTimeSlotController;
use App\Http\Controllers\Web\AdminUserController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\PackageSamplePhotoController;
use App\Http\Controllers\Web\QueueBoardController;
use App\Http\Controllers\Web\WalkInRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/queue-board', [QueueBoardController::class, 'index'])->name('queue.board');
Route::prefix('walk-in')->name('walk-in.')->group(function () {
    Route::get('/', [WalkInRequestController::class, 'create'])->name('create');
    Route::post('/', [WalkInRequestController::class, 'store'])->middleware('throttle:walk-in')->name('store');
    Route::get('/success/{walkInRequest:request_code}', [WalkInRequestController::class, 'success'])->name('success');
});
Route::get('/media/package-samples/{path}', PackageSamplePhotoController::class)
    ->where('path', '.*')
    ->name('media.package-samples');

Route::prefix('booking')->name('booking.')->group(function () {
    Route::get('/customer', [BookingController::class, 'customer'])->name('customer');
    Route::post('/customer', [BookingController::class, 'storeCustomer'])->name('customer.store');
    Route::get('/', [BookingController::class, 'create'])->name('create');
    Route::get('/availability', [BookingController::class, 'availability'])->name('availability');
    Route::get('/payment', [BookingController::class, 'payment'])->name('payment');
    Route::post('/payment', [BookingController::class, 'preparePayment'])->name('payment.prepare');
    Route::post('/', [BookingController::class, 'store'])->name('store');
    Route::get('/success/{booking:booking_code}', [BookingController::class, 'success'])->name('success');
});
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.attempt');

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/admin-dashboard', fn () => redirect()->route('admin.dashboard'));
        Route::get('/packages', AdminDashboardController::class);
        Route::get('/add-ons', AdminDashboardController::class);
        Route::get('/stock', AdminDashboardController::class);
        Route::get('/design-catalogs', AdminDashboardController::class);
        Route::get('/users', AdminDashboardController::class);
        Route::get('/bookings', AdminDashboardController::class);
        Route::get('/queue-tickets', AdminDashboardController::class);
        Route::get('/transactions', AdminDashboardController::class);
        Route::get('/reports', AdminDashboardController::class);
        Route::get('/referrals', AdminDashboardController::class);
        Route::get('/activity-logs', AdminDashboardController::class);
        Route::get('/settings', AdminDashboardController::class);
        Route::get('/branches', AdminDashboardController::class);
        Route::get('/time-slots', AdminDashboardController::class);
        Route::get('/blackout-dates', AdminDashboardController::class);
        Route::get('/printer-settings', AdminDashboardController::class);
        Route::get('/payments', AdminDashboardController::class);
        Route::get('/cashier-settlements', AdminDashboardController::class);
        Route::get('/app-settings', AdminDashboardController::class);

        Route::get('/dashboard-data', AdminDashboardDataController::class)
            ->middleware('admin.permission:booking.view,queue.view,transaction.view,inventory.view,report.view,catalog.manage,settings.manage,user.manage,payment.manage')
            ->name('dashboard.data');
        Route::get('/dashboard-report', AdminDashboardReportController::class)
            ->middleware('admin.permission:report.view')
            ->name('dashboard.report');

        Route::get('/packages-data', [AdminPackageController::class, 'index'])->middleware('admin.permission:catalog.manage')->name('packages.data');
        Route::post('/packages', [AdminPackageController::class, 'store'])->middleware('admin.permission:catalog.manage')->name('packages.store');
        Route::put('/packages/{package}', [AdminPackageController::class, 'update'])->middleware('admin.permission:catalog.manage');
        Route::delete('/packages/{package}', [AdminPackageController::class, 'destroy'])->middleware('admin.permission:catalog.manage');

        Route::get('/add-ons-data', [AdminAddOnController::class, 'index'])->middleware('admin.permission:catalog.manage')->name('add-ons.data');
        Route::post('/add-ons', [AdminAddOnController::class, 'store'])->middleware('admin.permission:catalog.manage')->name('add-ons.store');
        Route::put('/add-ons/{addOn}', [AdminAddOnController::class, 'update'])->middleware('admin.permission:catalog.manage');
        Route::delete('/add-ons/{addOn}', [AdminAddOnController::class, 'destroy'])->middleware('admin.permission:catalog.manage');

        Route::get('/stock-data', [AdminInventoryController::class, 'index'])->middleware('admin.permission:inventory.view')->name('stock.data');
        Route::post('/inventory-items', [AdminInventoryController::class, 'store'])->middleware('admin.permission:settings.manage')->name('inventory-items.store');
        Route::put('/inventory-items/{inventoryItem}', [AdminInventoryController::class, 'update'])->middleware('admin.permission:settings.manage');
        Route::delete('/inventory-items/{inventoryItem}', [AdminInventoryController::class, 'destroy'])->middleware('admin.permission:settings.manage');
        Route::post('/inventory-items/{inventoryItem}/movement', [AdminInventoryController::class, 'movement'])->middleware('admin.permission:settings.manage')->name('inventory-items.movement');

        Route::get('/designs-data', [AdminDesignController::class, 'index'])->middleware('admin.permission:catalog.manage')->name('designs.data');
        Route::post('/designs', [AdminDesignController::class, 'store'])->middleware('admin.permission:catalog.manage')->name('designs.store');
        Route::put('/designs/{designCatalog}', [AdminDesignController::class, 'update'])->middleware('admin.permission:catalog.manage');
        Route::delete('/designs/{designCatalog}', [AdminDesignController::class, 'destroy'])->middleware('admin.permission:catalog.manage');

        Route::get('/users-data', [AdminUserController::class, 'index'])->middleware('admin.permission:user.manage')->name('users.data');
        Route::post('/users', [AdminUserController::class, 'store'])->middleware('admin.permission:user.manage')->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->middleware('admin.permission:user.manage');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->middleware('admin.permission:user.manage');

        Route::get('/queue-data', [AdminQueueController::class, 'index'])->middleware('admin.permission:queue.view')->name('queue.data');
        Route::post('/queue/call-next', [AdminQueueController::class, 'callNext'])->middleware('admin.permission:queue.manage')->name('queue.call-next');
        Route::post('/queue/check-in', [AdminQueueController::class, 'checkIn'])->middleware('admin.permission:queue.manage')->name('queue.check-in');
        Route::post('/queue/walk-in', [AdminQueueController::class, 'walkIn'])->middleware('admin.permission:queue.manage')->name('queue.walk-in');
        Route::patch('/queue/{queueTicket}/status', [AdminQueueController::class, 'transition'])->middleware('admin.permission:queue.manage');

        Route::get('/settings-data', [AdminSettingsController::class, 'index'])->middleware('admin.permission:settings.manage')->name('settings.data');
        Route::put('/settings/default-branch', [AdminSettingsController::class, 'updateDefaultBranch'])->middleware('admin.permission:settings.manage')->name('settings.default-branch');
        Route::post('/settings/branches', [AdminSettingsController::class, 'storeBranch'])->middleware('admin.permission:settings.manage')->name('settings.branches.store');
        Route::put('/settings/branches/{branch}', [AdminSettingsController::class, 'updateBranch'])->middleware('admin.permission:settings.manage');
        Route::delete('/settings/branches/{branch}', [AdminSettingsController::class, 'destroyBranch'])->middleware('admin.permission:settings.manage');

        Route::get('/branches-data', [AdminBranchController::class, 'index'])->middleware('admin.permission:settings.manage')->name('branches.data');
        Route::post('/branches', [AdminBranchController::class, 'store'])->middleware('admin.permission:settings.manage')->name('branches.store');
        Route::put('/branches/{branch}', [AdminBranchController::class, 'update'])->middleware('admin.permission:settings.manage');
        Route::delete('/branches/{branch}', [AdminBranchController::class, 'destroy'])->middleware('admin.permission:settings.manage');

        Route::get('/time-slots-data', [AdminTimeSlotController::class, 'index'])->middleware('admin.permission:settings.manage')->name('time-slots.data');
        Route::post('/time-slots', [AdminTimeSlotController::class, 'store'])->middleware('admin.permission:settings.manage')->name('time-slots.store');
        Route::put('/time-slots/{timeSlot}', [AdminTimeSlotController::class, 'update'])->middleware('admin.permission:settings.manage');
        Route::delete('/time-slots/{timeSlot}', [AdminTimeSlotController::class, 'destroy'])->middleware('admin.permission:settings.manage');
        Route::post('/time-slots/generate', [AdminTimeSlotController::class, 'generate'])->middleware('admin.permission:settings.manage')->name('time-slots.generate');
        Route::post('/time-slots/bulk-bookable', [AdminTimeSlotController::class, 'bulkBookable'])->middleware('admin.permission:settings.manage')->name('time-slots.bulk-bookable');

        Route::get('/blackout-dates-data', [AdminBlackoutDateController::class, 'index'])->middleware('admin.permission:settings.manage')->name('blackout-dates.data');
        Route::post('/blackout-dates', [AdminBlackoutDateController::class, 'store'])->middleware('admin.permission:settings.manage')->name('blackout-dates.store');
        Route::put('/blackout-dates/{blackoutDate}', [AdminBlackoutDateController::class, 'update'])->middleware('admin.permission:settings.manage');
        Route::delete('/blackout-dates/{blackoutDate}', [AdminBlackoutDateController::class, 'destroy'])->middleware('admin.permission:settings.manage');

        Route::get('/printer-settings-data', [AdminPrinterSettingController::class, 'index'])->middleware('admin.permission:settings.manage')->name('printer-settings.data');
        Route::post('/printer-settings', [AdminPrinterSettingController::class, 'store'])->middleware('admin.permission:settings.manage')->name('printer-settings.store');
        Route::put('/printer-settings/{printerSetting}', [AdminPrinterSettingController::class, 'update'])->middleware('admin.permission:settings.manage');
        Route::delete('/printer-settings/{printerSetting}', [AdminPrinterSettingController::class, 'destroy'])->middleware('admin.permission:settings.manage');
        Route::patch('/printer-settings/{printerSetting}/default', [AdminPrinterSettingController::class, 'setDefault'])->middleware('admin.permission:settings.manage')->name('printer-settings.set-default');

        Route::get('/payments-data', [AdminPaymentController::class, 'index'])->middleware('admin.permission:transaction.view,payment.manage')->name('payments.data');
        Route::post('/payments/{transaction}/store', [AdminPaymentController::class, 'store'])->middleware('admin.permission:payment.manage')->name('payments.store');

        Route::get('/cashier-settlements-data', [AdminCashierSettlementController::class, 'index'])->middleware('admin.permission:report.view')->name('cashier-settlements.data');
        Route::get('/cashier-settlements/{cashierSettlement}', [AdminCashierSettlementController::class, 'show'])->middleware('admin.permission:report.view');
        Route::post('/cashier-settlements/{cashierSettlement}/verify', [AdminCashierSettlementController::class, 'verify'])->middleware('admin.permission:report.view');
        Route::post('/cashier-settlements/{cashierSettlement}/correction', [AdminCashierSettlementController::class, 'correction'])->middleware('admin.permission:report.view');

        Route::get('/referrals-data', [AdminReferralController::class, 'index'])->middleware('admin.permission:settings.manage')->name('referrals.data');
        Route::post('/referrals', [AdminReferralController::class, 'store'])->middleware('admin.permission:settings.manage')->name('referrals.store');
        Route::put('/referrals/{referralCode}', [AdminReferralController::class, 'update'])->middleware('admin.permission:settings.manage');
        Route::delete('/referrals/{referralCode}', [AdminReferralController::class, 'destroy'])->middleware('admin.permission:settings.manage');

        Route::get('/app-settings-data', [AdminAppSettingController::class, 'index'])->middleware('admin.permission:settings.manage')->name('app-settings.data');
        Route::put('/app-settings/{group}', [AdminAppSettingController::class, 'update'])->middleware('admin.permission:settings.manage')->name('app-settings.update');

        Route::post('/bookings', [AdminBookingController::class, 'store'])->middleware('admin.permission:booking.manage')->name('bookings.store');
        Route::put('/bookings/{booking}', [AdminBookingController::class, 'update'])->middleware('admin.permission:booking.manage');
        Route::delete('/bookings/{booking}', [AdminBookingController::class, 'destroy'])->middleware('admin.permission:booking.manage');
        Route::post('/bookings/{booking}/confirm', [AdminBookingController::class, 'confirm'])->middleware('admin.permission:booking.manage');
        Route::post('/bookings/{booking}/confirm-payment', [AdminBookingController::class, 'confirmPayment'])->middleware('admin.permission:booking.manage,payment.manage');
        Route::post('/bookings/{booking}/decline', [AdminBookingController::class, 'decline'])->middleware('admin.permission:booking.manage');
        Route::get('/bookings/{booking}/transfer-proof', [AdminBookingController::class, 'transferProof'])->middleware('admin.permission:booking.view')->name('bookings.transfer-proof');
    });
});
