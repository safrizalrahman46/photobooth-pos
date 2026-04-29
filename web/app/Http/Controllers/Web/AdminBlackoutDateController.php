<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreBlackoutDateRequest;
use App\Http\Requests\AdminUpdateBlackoutDateRequest;
use App\Models\BlackoutDate;
use App\Services\AdminBlackoutDateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBlackoutDateController extends Controller
{
    public function index(Request $request, AdminBlackoutDateService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'blackout_dates' => $service->rows([
                    'branch_id' => $request->integer('branch_id'),
                    'blackout_date' => $request->string('blackout_date')->toString(),
                    'search' => $request->string('search')->toString(),
                    'is_closed' => $request->has('is_closed') ? $request->boolean('is_closed') : null,
                ]),
            ],
        ]);
    }

    public function store(AdminStoreBlackoutDateRequest $request, AdminBlackoutDateService $service): JsonResponse
    {
        $service->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Blackout date created successfully.',
            'data' => [
                'blackout_dates' => $service->rows(),
            ],
        ], 201);
    }

    public function update(
        AdminUpdateBlackoutDateRequest $request,
        BlackoutDate $blackoutDate,
        AdminBlackoutDateService $service,
    ): JsonResponse {
        $service->update($blackoutDate, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Blackout date updated successfully.',
            'data' => [
                'blackout_dates' => $service->rows(),
            ],
        ]);
    }

    public function destroy(BlackoutDate $blackoutDate, AdminBlackoutDateService $service): JsonResponse
    {
        $service->destroy($blackoutDate);

        return response()->json([
            'success' => true,
            'message' => 'Blackout date deleted successfully.',
            'data' => [
                'blackout_dates' => $service->rows(),
            ],
        ]);
    }
}

