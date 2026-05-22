<?php

namespace App\Http\Controllers\Web;

use App\Http\Requests\AdminStorePackageRequest;
use App\Http\Requests\AdminUpdatePackageRequest;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\AdminPackageService;
use Illuminate\Http\JsonResponse;

class AdminPackageController extends Controller
{
    public function index(AdminPackageService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'packages' => $service->managementRows(),
            ],
        ]);
    }

    public function store(
        AdminStorePackageRequest $request,
        AdminPackageService $packageService,
    ): JsonResponse
    {
        $packageService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Paket berhasil dibuat.',
            'data' => [
                'packages' => $packageService->managementRows(),
            ],
        ], 201);
    }

    public function update(
        AdminUpdatePackageRequest $request,
        Package $package,
        AdminPackageService $packageService,
    ): JsonResponse
    {
        $packageService->update($package, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Paket berhasil diperbarui.',
            'data' => [
                'packages' => $packageService->managementRows(),
            ],
        ]);
    }

    public function destroy(Package $package, AdminPackageService $packageService): JsonResponse
    {
        $packageService->delete($package);

        return response()->json([
            'success' => true,
            'message' => 'Paket berhasil dihapus.',
            'data' => [
                'packages' => $packageService->managementRows(),
            ],
        ]);
    }

}
