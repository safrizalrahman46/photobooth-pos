<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreAddOnRequest;
use App\Http\Requests\AdminUpdateAddOnRequest;
use App\Models\AddOn;
use App\Services\AdminAddOnService;
use App\Services\AdminDashboardDataService;
use Illuminate\Http\JsonResponse;

class AdminAddOnController extends Controller
{
    public function index(AdminDashboardDataService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'add_ons' => $service->addOnManagementRows(),
            ],
        ]);
    }

    public function store(
        AdminStoreAddOnRequest $request,
        AdminAddOnService $addOnService,
        AdminDashboardDataService $service,
    ): JsonResponse
    {
        $addOnService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Add-on created successfully.',
            'data' => [
                'add_ons' => $service->addOnManagementRows(),
            ],
        ], 201);
    }

    public function update(
        AdminUpdateAddOnRequest $request,
        AddOn $addOn,
        AdminAddOnService $addOnService,
        AdminDashboardDataService $service,
    ): JsonResponse
    {
        $addOnService->update($addOn, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Add-on updated successfully.',
            'data' => [
                'add_ons' => $service->addOnManagementRows(),
            ],
        ]);
    }

    public function destroy(AddOn $addOn, AdminAddOnService $addOnService, AdminDashboardDataService $service): JsonResponse
    {
        $addOnService->delete($addOn);

        return response()->json([
            'success' => true,
            'message' => 'Add-on deleted successfully.',
            'data' => [
                'add_ons' => $service->addOnManagementRows(),
            ],
        ]);
    }
}
