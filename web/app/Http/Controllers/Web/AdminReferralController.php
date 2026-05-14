<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreReferralCodeRequest;
use App\Http\Requests\AdminUpdateReferralCodeRequest;
use App\Models\ReferralCode;
use App\Services\AdminReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReferralController extends Controller
{
    public function index(Request $request, AdminReferralService $service): JsonResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'code' => ['nullable', 'string', 'max:40'],
            'channel' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $service->payload($filters),
        ]);
    }

    public function store(AdminStoreReferralCodeRequest $request, AdminReferralService $service): JsonResponse
    {
        $service->create($request->validated(), $request->user()?->id ? (int) $request->user()->id : null);

        return response()->json([
            'success' => true,
            'message' => 'Kode referal berhasil dibuat.',
            'data' => $service->payload(),
        ], 201);
    }

    public function update(AdminUpdateReferralCodeRequest $request, ReferralCode $referralCode, AdminReferralService $service): JsonResponse
    {
        $service->update($referralCode, $request->validated(), $request->user()?->id ? (int) $request->user()->id : null);

        return response()->json([
            'success' => true,
            'message' => 'Kode referal berhasil diperbarui.',
            'data' => $service->payload(),
        ]);
    }

    public function destroy(Request $request, ReferralCode $referralCode, AdminReferralService $service): JsonResponse
    {
        $service->delete($referralCode, $request->user()?->id ? (int) $request->user()->id : null);

        return response()->json([
            'success' => true,
            'message' => 'Kode referal berhasil dihapus.',
            'data' => $service->payload(),
        ]);
    }
}
