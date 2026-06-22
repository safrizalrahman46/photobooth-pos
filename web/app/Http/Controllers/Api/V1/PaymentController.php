<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\TransactionResource;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly ApiResponder $responder,
    ) {}

    public function store(StorePaymentRequest $request, Transaction $transaction): JsonResponse
    {
        abort_unless($request->user()?->can('payment.manage'), 403);

        try {
            $updatedTransaction = $this->transactionService->addPayment(
                $transaction,
                $request->validated(),
                (int) $request->user()->id
            );
        } catch (ValidationException $exception) {
            return $this->responder->error(
                $exception->validator->errors()->first() ?: 'Pembayaran gagal ditambahkan.',
                422,
                $exception->errors(),
            );
        }

        $latestPayment = $updatedTransaction->payments()->latest('id')->first();

        return $this->responder->success([
            'transaction' => new TransactionResource($updatedTransaction->load('branch', 'booking', 'queueTicket', 'items', 'payments')),
            'payment' => $latestPayment ? new PaymentResource($latestPayment) : null,
        ], 'Pembayaran berhasil ditambahkan.');
    }

    public function updateMethod(UpdatePaymentMethodRequest $request, Payment $payment): JsonResponse
    {
        $user = $request->user();

        if ($user->cannot('payment.manage')) {
            abort(403);
        }

        $transaction = $payment->transaction;
        if (! $transaction) {
            return $this->responder->error('Pembayaran tidak memiliki transaksi.', 404);
        }

        $session = $transaction->cashierSession ?? $transaction->cashierSettlements()->first()?->cashierSession;
        if ($session && $session->status === 'closed') {
            return $this->responder->error('Sesi kasir sudah ditutup. Tidak dapat mengubah metode pembayaran.', 422);
        }

        $beforeMethod = $payment->method instanceof \App\Enums\PaymentMethod
            ? $payment->method->value
            : (string) $payment->method;

        $payment->method = $request->validated('method');

        $meta = $payment->meta ?? [];
        $meta['method_history'] = $meta['method_history'] ?? [];
        $meta['method_history'][] = [
            'from' => $beforeMethod,
            'to' => $request->validated('method'),
            'changed_by' => (int) $user->id,
            'changed_by_name' => (string) ($user->name ?? ''),
            'reason' => $request->validated('reason', ''),
            'changed_at' => now()->toIso8601String(),
        ];
        $meta['method_updated_by'] = (int) $user->id;
        $meta['method_updated_at'] = now()->toIso8601String();

        $payment->meta = $meta;
        $payment->save();

        return $this->responder->success(
            new PaymentResource($payment->fresh()),
            'Metode pembayaran berhasil diubah.'
        );
    }
}
