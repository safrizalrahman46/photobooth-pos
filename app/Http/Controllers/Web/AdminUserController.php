<?php

namespace App\Http\Controllers\Web;

use App\Http\Requests\AdminStoreUserRequest;
use App\Http\Controllers\Controller;
use App\Services\AdminDashboardDataService;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function index(AdminDashboardDataService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'users' => $service->userManagementRows(),
                'roles' => $service->userRoleOptions(),
            ],
        ]);
    }

    public function store(AdminStoreUserRequest $request, AdminUserService $userService, AdminDashboardDataService $service): JsonResponse
    {
        $userService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => [
                'users' => $service->userManagementRows(),
                'roles' => $service->userRoleOptions(),
            ],
        ], 201);
    }
}
