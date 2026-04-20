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
<<<<<<< HEAD
        abort_unless($request->user()?->can('report.view'), 403);

        $payload = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);
=======
        $payload = $request->validated();
>>>>>>> fc7ace865dfae888f032ba57ff5855d596c41b93

        $from = $payload['from'];
        $to = $payload['to'];
        $branchId = $payload['branch_id'] ?? null;

        return $this->responder->success([
            'sales' => $this->reportService->salesSummary($from, $to, $branchId),
            'bookings' => $this->reportService->bookingVolume($from, $to, $branchId),
            'queue' => $this->reportService->queueVolume($from, $to, $branchId),
        ], 'Ringkasan laporan berhasil dimuat.');
    }
}
