<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStorePaymentRequest;
use App\Models\Transaction;
use App\Services\AdminPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request, AdminPaymentService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'payments' => $service->rows([
                    'branch_id' => $request->integer('branch_id'),
                    'method' => $request->string('method')->toString(),
                    'paid_date' => $request->string('paid_date')->toString(),
                    'transaction_status' => $request->string('transaction_status')->toString(),
                    'limit' => $request->integer('limit', 150),
                ]),
                'transaction_options' => $service->transactionOptions(),
            ],
        ]);
    }

    public function store(
        AdminStorePaymentRequest $request,
        Transaction $transaction,
        AdminPaymentService $service,
    ): JsonResponse {
        $service->storePayment($transaction, $request->validated(), (int) $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Payment added successfully.',
            'data' => [
                'payments' => $service->rows(),
                'transaction_options' => $service->transactionOptions(),
            ],
        ], 201);
    }
}

