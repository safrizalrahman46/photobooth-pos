<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUpdateAppSettingRequest;
use App\Services\AdminAppSettingService;
use Illuminate\Http\JsonResponse;

class AdminAppSettingController extends Controller
{
    public function index(AdminAppSettingService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'app_settings' => $service->rows(),
            ],
        ]);
    }

    public function update(string $group, AdminUpdateAppSettingRequest $request, AdminAppSettingService $service): JsonResponse
    {
        $settings = $service->updateGroup($group, $request->validated('value', []), $request->user()?->id);

        return response()->json([
            'success' => true,
            'message' => 'App setting updated successfully.',
            'data' => [
                'app_settings' => $settings,
            ],
        ]);
    }
}

