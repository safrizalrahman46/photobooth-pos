<?php

namespace App\Http\Controllers\Web;

use App\Http\Requests\AdminStoreDesignRequest;
use App\Http\Requests\AdminUpdateDesignRequest;
use App\Http\Controllers\Controller;
use App\Models\DesignCatalog;
use App\Services\AdminDashboardDataService;
use App\Services\AdminDesignService;
use Illuminate\Http\JsonResponse;

class AdminDesignController extends Controller
{
    public function index(AdminDashboardDataService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'designs' => $service->designManagementRows(),
            ],
        ]);
    }

    public function store(
        AdminStoreDesignRequest $request,
        AdminDesignService $designService,
        AdminDashboardDataService $service,
    ): JsonResponse
    {
        $designService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Design created successfully.',
            'data' => [
                'designs' => $service->designManagementRows(),
            ],
        ], 201);
    }

    public function update(
        AdminUpdateDesignRequest $request,
        DesignCatalog $designCatalog,
        AdminDesignService $designService,
        AdminDashboardDataService $service,
    ): JsonResponse
    {
        $designService->update($designCatalog, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Design updated successfully.',
            'data' => [
                'designs' => $service->designManagementRows(),
            ],
        ]);
    }

    public function destroy(DesignCatalog $designCatalog, AdminDesignService $designService, AdminDashboardDataService $service): JsonResponse
    {
        $designService->delete($designCatalog);

        return response()->json([
            'success' => true,
            'message' => 'Design deleted successfully.',
            'data' => [
                'designs' => $service->designManagementRows(),
            ],
        ]);
    }

}
