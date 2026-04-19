<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AppSettingService;
use App\Services\AdminDashboardDataService;
use App\Services\AdminQueuePageService;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(
        AdminDashboardDataService $service,
        AdminQueuePageService $queuePageService,
        AppSettingService $appSettingService,
    ): View
    {
        $settingsPayload = $appSettingService->settingsPayload();

        $bootstrap = array_merge(
            $service->bootstrapPayload('', 'all', 15),
            [
                'queueLive' => $queuePageService->live(),
                'queueBookingOptions' => $queuePageService->bookingOptions(),
                'initialSettings' => $settingsPayload,
                'defaultBranchId' => $settingsPayload['default_branch_id'] ?? null,
            ],
            [
                'dataUrl' => route('admin.dashboard.data'),
                'reportUrl' => route('admin.dashboard.report'),
                'packagesDataUrl' => route('admin.packages.data'),
                'packageStoreUrl' => route('admin.packages.store'),
                'packageBaseUrl' => url('/admin/packages'),
                'addOnsDataUrl' => route('admin.add-ons.data'),
                'addOnStoreUrl' => route('admin.add-ons.store'),
                'addOnBaseUrl' => url('/admin/add-ons'),
                'designsDataUrl' => route('admin.designs.data'),
                'designStoreUrl' => route('admin.designs.store'),
                'designBaseUrl' => url('/admin/designs'),
                'usersDataUrl' => route('admin.users.data'),
                'userStoreUrl' => route('admin.users.store'),
                'queueDataUrl' => route('admin.queue.data'),
                'queueCallNextUrl' => route('admin.queue.call-next'),
                'queueCheckInUrl' => route('admin.queue.check-in'),
                'queueWalkInUrl' => route('admin.queue.walk-in'),
                'queueBaseUrl' => url('/admin/queue'),
                'settingsDataUrl' => route('admin.settings.data'),
                'settingsDefaultBranchUrl' => route('admin.settings.default-branch'),
                'settingsBranchStoreUrl' => route('admin.settings.branches.store'),
                'settingsBranchBaseUrl' => url('/admin/settings/branches'),
                'bookingStoreUrl' => route('admin.bookings.store'),
                'bookingBaseUrl' => url('/admin/bookings'),
                'bookingAvailabilityUrl' => route('booking.availability'),
                'panelUrl' => url('/admin'),
            ],
        );

        return view('web.admin-dashboard', [
            'bootstrap' => $bootstrap,
        ]);
    }
}
