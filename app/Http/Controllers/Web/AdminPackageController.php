<?php

namespace App\Http\Controllers\Web;

use App\Http\Requests\AdminStorePackageRequest;
use App\Http\Requests\AdminUpdatePackageRequest;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\AdminDashboardDataService;
use App\Services\AdminPackageService;
use Illuminate\Http\JsonResponse;

class AdminPackageController extends Controller
{
    public function index(AdminDashboardDataService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'packages' => $service->packageManagementRows(),
            ],
        ]);
    }

    public function store(
        AdminStorePackageRequest $request,
        AdminPackageService $packageService,
        AdminDashboardDataService $service,
    ): JsonResponse
    {
        $packageService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Package created successfully.',
            'data' => [
                'packages' => $service->packageManagementRows(),
            ],
        ], 201);
    }

    public function update(
        AdminUpdatePackageRequest $request,
        Package $package,
        AdminPackageService $packageService,
        AdminDashboardDataService $service,
    ): JsonResponse
    {
        $packageService->update($package, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Package updated successfully.',
            'data' => [
                'packages' => $service->packageManagementRows(),
            ],
        ]);
    }

    public function destroy(Package $package, AdminPackageService $packageService, AdminDashboardDataService $service): JsonResponse
    {
        $packageService->delete($package);

        return response()->json([
            'success' => true,
            'message' => 'Package deleted successfully.',
            'data' => [
                'packages' => $service->packageManagementRows(),
            ],
        ]);
    }

}
