<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardDataController extends Controller
{
    public function __invoke(Request $request, AdminDashboardDataService $service): JsonResponse
    {
        $payload = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:all,pending,booked,used,expired'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $rowsPayload = $service->rowsPayload(
            (string) ($payload['search'] ?? ''),
            (string) ($payload['status'] ?? 'all'),
            (int) ($payload['per_page'] ?? 15),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => $rowsPayload['rows'],
                'pagination' => $rowsPayload['pagination'],
            ],
        ]);
    }
}
