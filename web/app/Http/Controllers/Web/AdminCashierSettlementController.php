<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CashierSettlement;
use App\Services\CashierSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminCashierSettlementController extends Controller
{
    public function index(Request $request, CashierSettlementService $service): JsonResponse
    {
        abort_unless($request->user()?->can('report.view'), 403);

        return response()->json([
            'success' => true,
            'data' => [
                'settlements' => $service->settlementRows([
                    'branch_id' => $request->integer('branch_id'),
                    'cashier_id' => $request->integer('cashier_id'),
                    'date' => $request->string('date')->toString(),
                    'search' => $request->string('search')->toString(),
                    'limit' => $request->integer('limit', 150),
                ]),
                'open_sessions' => $service->openSessionRows([
                    'branch_id' => $request->integer('branch_id'),
                ]),
            ],
        ]);
    }

    public function show(Request $request, CashierSettlement $cashierSettlement, CashierSettlementService $service): JsonResponse
    {
        abort_unless($request->user()?->can('report.view'), 403);

        return response()->json([
            'success' => true,
            'data' => [
                'settlement' => $service->mapSettlement($cashierSettlement->load(['branch', 'cashier', 'cashierSession', 'corrections.creator'])),
            ],
        ]);
    }

    public function verify(Request $request, CashierSettlement $cashierSettlement, CashierSettlementService $service): JsonResponse
    {
        abort_unless($request->user()?->can('report.view'), 403);

        $payload = $request->validate([
            'owner_received_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $settlement = $service->verifyCash(
            $cashierSettlement,
            (float) $payload['owner_received_cash'],
            (int) $request->user()->id,
            $payload['notes'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => 'Setoran kasir berhasil dicocokkan.',
            'data' => [
                'settlement' => $service->mapSettlement($settlement),
                'settlements' => $service->settlementRows(),
                'open_sessions' => $service->openSessionRows(),
            ],
        ]);
    }

    public function correction(Request $request, CashierSettlement $cashierSettlement, CashierSettlementService $service): JsonResponse
    {
        abort_unless($request->user()?->can('report.view'), 403);

        $payload = $request->validate([
            'amount' => ['required', 'numeric', 'not_in:0'],
            'affects_cash' => ['nullable', 'boolean'],
            'reason' => ['required', 'string', 'max:180'],
        ]);

        try {
            $service->addCorrection($cashierSettlement, $payload, (int) $request->user()->id);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->validator->errors()->first() ?: 'Koreksi setoran gagal.',
                'errors' => $exception->errors(),
            ], 422);
        }

        $settlement = $cashierSettlement->refresh()->load(['branch', 'cashier', 'cashierSession', 'corrections.creator']);

        return response()->json([
            'success' => true,
            'message' => 'Koreksi setoran berhasil ditambahkan.',
            'data' => [
                'settlement' => $service->mapSettlement($settlement),
                'settlements' => $service->settlementRows(),
                'open_sessions' => $service->openSessionRows(),
            ],
        ], 201);
    }
}
