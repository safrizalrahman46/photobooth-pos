<?php

namespace App\Http\Controllers\Web;

use App\Http\Requests\AdminStoreDesignRequest;
use App\Http\Requests\AdminUpdateDesignRequest;
use App\Http\Controllers\Controller;
use App\Models\DesignCatalog;
use App\Services\AdminDesignService;
use Illuminate\Http\JsonResponse;

class AdminDesignController extends Controller
{
    public function index(AdminDesignService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'designs' => $service->managementRows(),
            ],
        ]);
    }

    public function store(
        AdminStoreDesignRequest $request,
        AdminDesignService $designService,
    ): JsonResponse
    {
        $designService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Desain berhasil dibuat.',
            'data' => [
                'designs' => $designService->managementRows(),
            ],
        ], 201);
    }

    public function update(
        AdminUpdateDesignRequest $request,
        DesignCatalog $designCatalog,
        AdminDesignService $designService,
    ): JsonResponse
    {
        $designService->update($designCatalog, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Desain berhasil diperbarui.',
            'data' => [
                'designs' => $designService->managementRows(),
            ],
        ]);
    }

    public function destroy(DesignCatalog $designCatalog, AdminDesignService $designService): JsonResponse
    {
        $designService->delete($designCatalog);

        return response()->json([
            'success' => true,
            'message' => 'Desain berhasil dihapus.',
            'data' => [
                'designs' => $designService->managementRows(),
            ],
        ]);
    }

}
