<?php

namespace App\Http\Controllers\Web;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTransactionController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        abort_unless($request->user()?->can('transaction.manage'), 403, 'Anda tidak memiliki hak akses untuk mengubah transaksi ini.');

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
            'discount_amount' => 'nullable|numeric|min:0|max:999999999',
        ]);

        return DB::transaction(function () use ($validated, $transaction, $request): JsonResponse {
            $hasChanges = false;
            $oldDiscount = (float) $transaction->discount_amount;
            $oldNotes = $transaction->notes;

            if (array_key_exists('discount_amount', $validated)) {
                $newDiscount = round((float) $validated['discount_amount'], 2);
                $subtotal = (float) $transaction->subtotal;

                if ($newDiscount > $subtotal) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nominal diskon tidak boleh melebihi subtotal transaksi (' . number_format($subtotal, 0, ',', '.') . ').',
                    ], 422);
                }

                if (abs($newDiscount - $oldDiscount) > 0.001) {
                    $transaction->discount_amount = $newDiscount;
                    $referralDiscount = (float) $transaction->referral_discount_amount;
                    $tax = (float) $transaction->tax_amount;
                    $transaction->total_amount = max(0, round($subtotal - $newDiscount - $referralDiscount + $tax, 2));
                    $hasChanges = true;
                }
            }

            if (array_key_exists('notes', $validated)) {
                $newNotes = $validated['notes'];
                if ($newNotes !== $oldNotes) {
                    $transaction->notes = $newNotes;
                    $hasChanges = true;
                }
            }

            if ($hasChanges) {
                $total = (float) $transaction->total_amount;
                $paid = (float) $transaction->paid_amount;

                $transaction->change_amount = max(0, round($paid - $total, 2));

                if ($paid <= 0) {
                    $transaction->status = TransactionStatus::Unpaid;
                } elseif ($paid >= $total) {
                    $transaction->status = TransactionStatus::Paid;
                } else {
                    $transaction->status = TransactionStatus::Partial;
                }

                $transaction->save();

                $this->activityLogger->log(
                    'transactions',
                    'updated',
                    (int) $request->user()->id,
                    Transaction::class,
                    (int) $transaction->id,
                    [
                        'message' => sprintf('Transaksi %s diperbarui oleh admin.', (string) $transaction->transaction_code),
                        'label' => (string) $transaction->transaction_code,
                        'branch_id' => $transaction->branch_id ? (int) $transaction->branch_id : null,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diperbarui.',
                'data' => [
                    'record_id' => (int) $transaction->id,
                    'total_amount' => (float) $transaction->total_amount,
                    'discount_amount' => (float) $transaction->discount_amount,
                    'status' => (string) $transaction->status->value,
                    'notes' => (string) ($transaction->notes ?? ''),
                ],
            ]);
        });
    }
}
