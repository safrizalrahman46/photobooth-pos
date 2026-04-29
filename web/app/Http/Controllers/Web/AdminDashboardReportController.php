<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminDashboardReportController extends Controller
{
    public function __invoke(Request $request, AdminDashboardDataService $service): JsonResponse
    {
        $payload = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'cashier_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $from = ! empty($payload['from'])
            ? Carbon::createFromFormat('Y-m-d', (string) $payload['from'])
            : null;

        $to = ! empty($payload['to'])
            ? Carbon::createFromFormat('Y-m-d', (string) $payload['to'])
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $service->reportSummary(
                    $from,
                    $to,
                    isset($payload['package_id']) ? (int) $payload['package_id'] : null,
                    isset($payload['cashier_id']) ? (int) $payload['cashier_id'] : null,
                ),
            ],
        ]);
    }
}
