<?php

namespace App\Http\Controllers\Web;

use App\Http\Requests\AdminStoreUserRequest;
use App\Http\Requests\AdminUpdateUserRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(AdminUserService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'users' => $service->rows(),
                'roles' => $service->roleOptions(),
            ],
        ]);
    }

    public function store(AdminStoreUserRequest $request, AdminUserService $userService): JsonResponse
    {
        abort_unless($request->user()?->hasRole('owner'), 403);

        $userService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => [
                'users' => $userService->rows(),
                'roles' => $userService->roleOptions(),
            ],
        ], 201);
    }

    public function update(
        AdminUpdateUserRequest $request,
        User $user,
        AdminUserService $userService,
    ): JsonResponse {
        abort_unless($request->user()?->hasRole('owner'), 403);

        $userService->update($user, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => [
                'users' => $userService->rows(),
                'roles' => $userService->roleOptions(),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        User $user,
        AdminUserService $userService,
    ): JsonResponse {
        abort_unless($request->user()?->hasRole('owner'), 403);

        $userService->delete($user, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
            'data' => [
                'users' => $userService->rows(),
                'roles' => $userService->roleOptions(),
            ],
        ]);
    }
}
