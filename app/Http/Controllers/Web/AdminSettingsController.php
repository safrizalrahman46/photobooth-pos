<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\AppSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSettingsController extends Controller
{
    public function index(AppSettingService $appSettingService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $appSettingService->settingsPayload(),
            ],
        ]);
    }

    public function updateDefaultBranch(Request $request, AppSettingService $appSettingService): JsonResponse
    {
        $payload = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        $updated = $appSettingService->updateDefaultBranch(
            (int) $payload['branch_id'],
            $request->user()?->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Default branch updated successfully.',
            'data' => [
                'settings' => $updated,
            ],
        ]);
    }

    public function storeBranch(Request $request, AppSettingService $appSettingService): JsonResponse
    {
        $payload = $this->validateBranchPayload($request);

        $updated = $appSettingService->createBranch(
            $payload,
            $request->user()?->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully.',
            'data' => [
                'settings' => $updated,
            ],
        ], 201);
    }

    public function updateBranch(Request $request, Branch $branch, AppSettingService $appSettingService): JsonResponse
    {
        $payload = $this->validateBranchPayload($request);

        $updated = $appSettingService->updateBranch($branch, $payload);

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully.',
            'data' => [
                'settings' => $updated,
            ],
        ]);
    }

    public function destroyBranch(Request $request, Branch $branch, AppSettingService $appSettingService): JsonResponse
    {
        $updated = $appSettingService->deactivateBranch(
            $branch,
            $request->user()?->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Branch removed from active list.',
            'data' => [
                'settings' => $updated,
            ],
        ]);
    }

    private function validateBranchPayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);
    }
}