<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreBranchRequest;
use App\Http\Requests\AdminUpdateBranchRequest;
use App\Models\Branch;
use App\Services\AdminBranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBranchController extends Controller
{
    public function index(Request $request, AdminBranchService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'branches' => $service->rows([
                    'include_inactive' => $request->boolean('include_inactive', true),
                    'search' => $request->string('search')->toString(),
                ]),
            ],
        ]);
    }

    public function store(AdminStoreBranchRequest $request, AdminBranchService $service): JsonResponse
    {
        $service->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully.',
            'data' => [
                'branches' => $service->rows(),
            ],
        ], 201);
    }

    public function update(AdminUpdateBranchRequest $request, Branch $branch, AdminBranchService $service): JsonResponse
    {
        $service->update($branch, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully.',
            'data' => [
                'branches' => $service->rows(),
            ],
        ]);
    }

    public function destroy(Branch $branch, AdminBranchService $service): JsonResponse
    {
        $action = $service->destroy($branch);
        $message = $action === 'deactivated'
            ? 'Branch has related data and was deactivated.'
            : 'Branch deleted successfully.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'branches' => $service->rows(),
            ],
        ]);
    }
}

