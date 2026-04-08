<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly ApiResponder $responder,
    ) {}

    public function store(StorePaymentRequest $request, Transaction $transaction): JsonResponse
    {
        $updatedTransaction = $this->transactionService->addPayment(
            $transaction,
            $request->validated(),
            (int) $request->user()->id
        );

        $latestPayment = $updatedTransaction->payments()->latest('id')->first();

        return $this->responder->success([
            'transaction' => new TransactionResource($updatedTransaction->load('items', 'payments')),
            'payment' => $latestPayment ? new PaymentResource($latestPayment) : null,
        ], 'Pembayaran berhasil ditambahkan.');
    }
}
