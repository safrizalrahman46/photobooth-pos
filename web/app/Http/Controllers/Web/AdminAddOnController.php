<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreAddOnRequest;
use App\Http\Requests\AdminUpdateAddOnRequest;
use App\Models\AddOn;
use App\Services\AdminAddOnService;
use Illuminate\Http\JsonResponse;

class AdminAddOnController extends Controller
{
    public function index(AdminAddOnService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'add_ons' => $service->managementRows(),
            ],
        ]);
    }

    public function store(
        AdminStoreAddOnRequest $request,
        AdminAddOnService $addOnService,
    ): JsonResponse
    {
        $addOnService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Add-on berhasil dibuat.',
            'data' => [
                'add_ons' => $addOnService->managementRows(),
            ],
        ], 201);
    }

    public function update(
        AdminUpdateAddOnRequest $request,
        AddOn $addOn,
        AdminAddOnService $addOnService,
    ): JsonResponse
    {
        $addOnService->update($addOn, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Add-on berhasil diperbarui.',
            'data' => [
                'add_ons' => $addOnService->managementRows(),
            ],
        ]);
    }

    public function destroy(AddOn $addOn, AdminAddOnService $addOnService): JsonResponse
    {
        $addOnService->delete($addOn);

        return response()->json([
            'success' => true,
            'message' => 'Add-on berhasil dihapus.',
            'data' => [
                'add_ons' => $addOnService->managementRows(),
            ],
        ]);
    }
}
