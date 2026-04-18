<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardDataService;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(AdminDashboardDataService $service): View
    {
        $bootstrap = array_merge(
            $service->bootstrapPayload('', 'all', 15),
            [
                'dataUrl' => route('admin.dashboard.data'),
                'reportUrl' => route('admin.dashboard.report'),
                'panelUrl' => url('/panel'),
            ],
        );

        return view('web.admin-dashboard', [
            'bootstrap' => $bootstrap,
        ]);
    }
}
