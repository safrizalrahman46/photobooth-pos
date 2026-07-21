<?php

namespace App\Http\Controllers\Web;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStorePaymentRequest;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\AdminPaymentService;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminPaymentController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

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
        try {
            $service->storePayment($transaction, $request->validated(), (int) $request->user()->id);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->validator->errors()->first() ?: 'Pembayaran gagal ditambahkan.',
                'errors' => $exception->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil ditambahkan.',
            'data' => [
                'payments' => $service->rows(),
                'transaction_options' => $service->transactionOptions(),
            ],
        ], 201);
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage'), 403, 'Anda tidak memiliki hak akses untuk mengubah pembayaran.');

        $validated = $request->validate([
            'method' => ['nullable', Rule::in(['cash', 'qris', 'transfer', 'card'])],
            'amount' => 'nullable|numeric|min:0|max:999999999',
            'reference_no' => 'nullable|string|max:120',
            'paid_at' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($validated, $payment, $request): JsonResponse {
            $hasChanges = false;
            $oldMethod = $payment->method?->value;
            $oldAmount = (float) $payment->amount;
            $oldReference = $payment->reference_no;
            $oldPaidAt = $payment->paid_at?->toIso8601String();

            if (array_key_exists('method', $validated) && $validated['method'] !== $oldMethod) {
                $payment->method = PaymentMethod::from($validated['method']);
                $hasChanges = true;
            }

            if (array_key_exists('amount', $validated)) {
                $newAmount = round((float) $validated['amount'], 2);
                if (abs($newAmount - $oldAmount) > 0.001) {
                    $payment->amount = $newAmount;
                    $hasChanges = true;
                }
            }

            if (array_key_exists('reference_no', $validated) && $validated['reference_no'] !== $oldReference) {
                $payment->reference_no = $validated['reference_no'];
                $hasChanges = true;
            }

            if (array_key_exists('paid_at', $validated)) {
                $newPaidAt = $validated['paid_at'] ? Carbon::parse($validated['paid_at']) : null;
                $oldPaidAtCarbon = $payment->paid_at;
                if (($newPaidAt && !$oldPaidAtCarbon) || (!$newPaidAt && $oldPaidAtCarbon) || ($newPaidAt && $oldPaidAtCarbon && !$newPaidAt->equalTo($oldPaidAtCarbon))) {
                    $payment->paid_at = $newPaidAt;
                    $hasChanges = true;
                }
            }

            if (!$hasChanges) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada perubahan yang dilakukan.',
                ]);
            }

            $payment->save();

            $transaction = $payment->transaction;

            // Recalculate transaction totals if amount changed
            if (array_key_exists('amount', $validated)) {
                $totalPaid = (float) $transaction->payments()->sum('amount');
                $totalAmount = (float) $transaction->total_amount;
                $transaction->paid_amount = $totalPaid;
                $transaction->change_amount = max(0, round($totalPaid - $totalAmount, 2));

                if ($totalPaid <= 0) {
                    $transaction->status = TransactionStatus::Unpaid;
                } elseif ($totalPaid >= $totalAmount) {
                    $transaction->status = TransactionStatus::Paid;
                } else {
                    $transaction->status = TransactionStatus::Partial;
                }

                $transaction->save();
            }

            // Log activity
            $changedFields = [];
            if (array_key_exists('method', $validated) && $validated['method'] !== $oldMethod) {
                $changedFields[] = 'method';
            }
            if (array_key_exists('amount', $validated) && abs((float) $validated['amount'] - $oldAmount) > 0.001) {
                $changedFields[] = 'amount';
            }
            if (array_key_exists('reference_no', $validated) && $validated['reference_no'] !== $oldReference) {
                $changedFields[] = 'reference_no';
            }
            if (array_key_exists('paid_at', $validated)) {
                $changedFields[] = 'paid_at';
            }

            $this->activityLogger->log(
                'payments',
                'updated',
                (int) $request->user()->id,
                Payment::class,
                (int) $payment->id,
                [
                    'message' => sprintf(
                        'Pembayaran %s diubah (%s) oleh admin.',
                        (string) $payment->payment_code,
                        implode(', ', $changedFields)
                    ),
                    'label' => (string) $payment->payment_code,
                    'transaction_id' => (int) $transaction->id,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diperbarui.',
                'data' => [
                    'payment' => [
                        'id' => (int) $payment->id,
                        'method' => strtoupper((string) ($payment->method?->value ?? '')),
                        'method_lower' => (string) ($payment->method?->value ?? ''),
                        'amount' => (float) $payment->amount,
                        'amount_text' => 'Rp ' . number_format((float) $payment->amount, 0, ',', '.'),
                        'reference_no' => (string) ($payment->reference_no ?? ''),
                        'paid_at' => $payment->paid_at?->toIso8601String(),
                        'paid_at_text' => $payment->paid_at?->translatedFormat('d M Y, H:i') ?? '-',
                    ],
                    'transaction' => [
                        'record_id' => (int) $transaction->id,
                        'paid_amount' => (float) $transaction->paid_amount,
                        'paid_text' => 'Rp ' . number_format((float) $transaction->paid_amount, 0, ',', '.'),
                        'change_amount' => (float) $transaction->change_amount,
                        'change_text' => 'Rp ' . number_format((float) $transaction->change_amount, 0, ',', '.'),
                        'remaining_amount' => max(0, (float) $transaction->total_amount - (float) $transaction->paid_amount),
                        'remaining_text' => 'Rp ' . number_format(max(0, (float) $transaction->total_amount - (float) $transaction->paid_amount), 0, ',', '.'),
                        'status' => (string) $transaction->status->value,
                    ],
                ],
            ]);
        });
    }
}
