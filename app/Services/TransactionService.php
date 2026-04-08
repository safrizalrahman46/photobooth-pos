<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\TransactionStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Support\CodeGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private readonly CodeGenerator $codeGenerator,
    ) {}

    public function create(array $payload, int $cashierId): Transaction
    {
        return DB::transaction(function () use ($payload, $cashierId): Transaction {
            $now = Carbon::now();
            $items = $payload['items'];
            $subtotal = collect($items)->sum(fn (array $item) => (float) $item['qty'] * (float) $item['unit_price']);
            $discount = (float) ($payload['discount_amount'] ?? 0);
            $tax = (float) ($payload['tax_amount'] ?? 0);
            $total = max(0, $subtotal - $discount + $tax);

            $transaction = Transaction::query()->create([
                'transaction_code' => $this->codeGenerator->generateTransactionCode($now),
                'branch_id' => $payload['branch_id'],
                'booking_id' => $payload['booking_id'] ?? null,
                'queue_ticket_id' => $payload['queue_ticket_id'] ?? null,
                'cashier_id' => $cashierId,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'paid_amount' => 0,
                'change_amount' => 0,
                'status' => TransactionStatus::Unpaid,
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                TransactionItem::query()->create([
                    'transaction_id' => $transaction->id,
                    'item_type' => $item['item_type'],
                    'item_ref_id' => $item['item_ref_id'] ?? null,
                    'item_name' => $item['item_name'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => (float) $item['qty'] * (float) $item['unit_price'],
                ]);
            }

            return $transaction->refresh();
        });
    }

    public function addPayment(Transaction $transaction, array $payload, int $cashierId): Transaction
    {
        return DB::transaction(function () use ($transaction, $payload, $cashierId): Transaction {
            $amount = (float) $payload['amount'];

            Payment::query()->create([
                'transaction_id' => $transaction->id,
                'payment_code' => $this->codeGenerator->generatePaymentCode(now()),
                'method' => $payload['method'],
                'amount' => $amount,
                'reference_no' => $payload['reference_no'] ?? null,
                'paid_at' => now(),
                'cashier_id' => $cashierId,
                'notes' => $payload['notes'] ?? null,
            ]);

            $newPaidAmount = (float) $transaction->paid_amount + $amount;
            $totalAmount = (float) $transaction->total_amount;
            $change = max(0, $newPaidAmount - $totalAmount);

            $transaction->paid_amount = $newPaidAmount;
            $transaction->change_amount = $change;
            $transaction->paid_at = now();

            if ($newPaidAmount <= 0) {
                $transaction->status = TransactionStatus::Unpaid;
            } elseif ($newPaidAmount < $totalAmount) {
                $transaction->status = TransactionStatus::Partial;
            } else {
                $transaction->status = TransactionStatus::Paid;
            }

            $transaction->save();

            if ($transaction->booking && $transaction->status === TransactionStatus::Paid) {
                $booking = Booking::query()->find($transaction->booking_id);
                if ($booking) {
                    $booking->paid_amount = $newPaidAmount;
                    if ($booking->status !== BookingStatus::Done) {
                        $booking->status = BookingStatus::Paid;
                    }
                    $booking->save();
                }
            }

            return $transaction->refresh();
        });
    }
}
