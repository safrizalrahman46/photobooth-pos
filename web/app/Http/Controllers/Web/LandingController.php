<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Package;
use Illuminate\Contracts\View\View;
use Throwable;

class LandingController extends Controller
{
    public function index(): View
    {
        $branches = collect();
        $packages = collect();

        try {
            $branches = Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'timezone', 'address']);

            $packages = Package::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'duration_minutes', 'base_price', 'branch_id'])
                ->take(6);
        } catch (Throwable) {
        }

        return view('web.landing', [
            'branches' => $branches,
            'packages' => $packages,
        ]);
    }
}
