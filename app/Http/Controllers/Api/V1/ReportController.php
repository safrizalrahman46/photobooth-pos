<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportSummaryRequest;
use App\Services\ReportService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ApiResponder $responder,
    ) {}

    public function summary(ReportSummaryRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $from = $payload['from'];
        $to = $payload['to'];

        return $this->responder->success([
            'sales' => $this->reportService->salesSummary($from, $to),
            'bookings' => $this->reportService->bookingVolume($from, $to),
            'queue' => $this->reportService->queueVolume($from, $to),
        ], 'Ringkasan laporan berhasil dimuat.');
    }
}
