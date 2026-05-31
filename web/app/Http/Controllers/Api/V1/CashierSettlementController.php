<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CashierSettlement;
use App\Services\CashierSettlementService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashierSettlementController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
        private readonly CashierSettlementService $settlementService,
    ) {}

    public function show(Request $request, CashierSettlement $cashierSettlement): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage') || $request->user()?->can('report.view'), 403);
        abort_if((int) $cashierSettlement->cashier_id !== (int) $request->user()->id && ! $request->user()->can('report.view'), 403);

        return $this->responder->success(
            $this->settlementService->mapSettlement($cashierSettlement->load(['branch', 'cashier', 'cashierSession', 'corrections.creator'])),
            'Setoran kasir berhasil dimuat.'
        );
    }

    public function markPrinted(Request $request, CashierSettlement $cashierSettlement): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage') || $request->user()?->can('report.view'), 403);
        abort_if((int) $cashierSettlement->cashier_id !== (int) $request->user()->id && ! $request->user()->can('report.view'), 403);

        $settlement = $this->settlementService->markPrinted($cashierSettlement);

        return $this->responder->success(
            $this->settlementService->mapSettlement($settlement),
            'Status cetak setoran berhasil diperbarui.'
        );
    }
}
