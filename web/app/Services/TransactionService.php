<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\TransactionStatus;
use App\Models\AddOn;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Support\CodeGenerator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(
        private readonly CodeGenerator $codeGenerator,
        private readonly ActivityLogger $activityLogger,
        private readonly ReferralService $referralService,
        private readonly InventoryService $inventoryService,
        private readonly CashierSettlementService $cashierSettlementService,
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
                'referral_code_id' => $payload['referral_code_id'] ?? null,
                'referral_code' => $payload['referral_code'] ?? null,
                'referral_discount_amount' => (float) ($payload['referral_discount_amount'] ?? 0),
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

            $this->activityLogger->log(
                'transactions',
                'created',
                $cashierId,
                Transaction::class,
                (int) $transaction->id,
                [
                    'message' => sprintf('Transaksi %s dibuat.', (string) $transaction->transaction_code),
                    'label' => (string) $transaction->transaction_code,
                    'branch_id' => (int) $transaction->branch_id,
                    'booking_id' => $transaction->booking_id ? (int) $transaction->booking_id : null,
                    'queue_ticket_id' => $transaction->queue_ticket_id ? (int) $transaction->queue_ticket_id : null,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'referral_code' => $payload['referral_code'] ?? null,
                    'tax_amount' => $tax,
                    'total_amount' => $total,
                    'item_count' => count($items),
                ],
            );

            return $transaction->refresh();
        });
    }

    public function addPayment(Transaction $transaction, array $payload, int $cashierId): Transaction
    {
        return DB::transaction(function () use ($transaction, $payload, $cashierId): Transaction {
            /** @var Transaction $transaction */
            $transaction = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $session = $this->cashierSettlementService->requireActiveSession(
                $cashierId,
                (int) $transaction->branch_id,
                true,
            );
            $amount = (float) $payload['amount'];
            $beforePaidAmount = (float) $transaction->paid_amount;
            $totalAmount = (float) $transaction->total_amount;
            $remainingBeforePayment = max($totalAmount - $beforePaidAmount, 0);
            $netAmount = $remainingBeforePayment > 0 ? min($amount, $remainingBeforePayment) : $amount;
            $beforeStatus = $transaction->status instanceof TransactionStatus
                ? $transaction->status->value
                : (string) $transaction->status;
            $newPaidAmount = (float) $transaction->paid_amount + $amount;
            $paymentStage = (string) ($payload['payment_stage'] ?? $this->resolvePaymentStage(
                $payload,
                $beforePaidAmount,
                $newPaidAmount,
                $totalAmount,
            ));

            $payment = Payment::query()->create([
                'transaction_id' => $transaction->id,
                'cashier_session_id' => (int) $session->id,
                'payment_code' => $this->codeGenerator->generatePaymentCode(now()),
                'method' => $payload['method'],
                'payment_stage' => $paymentStage,
                'amount' => $amount,
                'net_amount' => $netAmount,
                'reference_no' => $payload['reference_no'] ?? null,
                'paid_at' => now(),
                'cashier_id' => $cashierId,
                'notes' => $payload['notes'] ?? null,
                'meta' => $payload['meta'] ?? null,
            ]);

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

            if ($transaction->status === TransactionStatus::Paid) {
                $this->referralService->markTransactionStatus($transaction, 'paid');
            }

            if ($transaction->booking && $transaction->status === TransactionStatus::Paid) {
                $booking = Booking::query()->find($transaction->booking_id);
                if ($booking) {
                    $beforeBookingStatus = $booking->status instanceof BookingStatus
                        ? $booking->status->value
                        : (string) $booking->status;

                    $booking->paid_amount = $newPaidAmount;
                    if ($booking->status !== BookingStatus::Done) {
                        $booking->status = BookingStatus::Paid;
                    }
                    $booking->save();
                    $this->referralService->markBookingStatus($booking, 'paid');

                    $afterBookingStatus = $booking->status instanceof BookingStatus
                        ? $booking->status->value
                        : (string) $booking->status;

                    if ($beforeBookingStatus !== $afterBookingStatus) {
                        $this->activityLogger->log(
                            'bookings',
                            'status_changed',
                            $cashierId,
                            Booking::class,
                            (int) $booking->id,
                            [
                                'message' => sprintf(
                                    'Status booking %s berubah dari %s ke %s melalui pembayaran transaksi.',
                                    (string) ($booking->booking_code ?? ('BK-'.$booking->id)),
                                    $beforeBookingStatus,
                                    $afterBookingStatus,
                                ),
                                'label' => (string) ($booking->booking_code ?? ('BK-'.$booking->id)),
                                'transaction_code' => (string) $transaction->transaction_code,
                                'from_status' => $beforeBookingStatus,
                                'to_status' => $afterBookingStatus,
                            ],
                        );
                    }
                }
            }

            $this->activityLogger->log(
                'payments',
                'created',
                $cashierId,
                Payment::class,
                (int) $payment->id,
                [
                    'message' => sprintf(
                        'Pembayaran %s ditambahkan ke transaksi %s.',
                        (string) $payment->payment_code,
                        (string) $transaction->transaction_code,
                    ),
                    'label' => (string) $payment->payment_code,
                    'transaction_code' => (string) $transaction->transaction_code,
                    'amount' => $amount,
                    'method' => (string) ($payment->method?->value ?? $payment->method),
                    'status_before' => $beforeStatus,
                    'status_after' => (string) ($transaction->status?->value ?? $transaction->status),
                    'paid_before' => $beforePaidAmount,
                    'paid_after' => $newPaidAmount,
                ],
            );

            return $transaction->refresh();
        });
    }

    private function resolvePaymentStage(array $payload, float $beforePaidAmount, float $newPaidAmount, float $totalAmount): string
    {
        $type = (string) data_get($payload, 'meta.type', '');

        if ($type === 'extra_print') {
            return CashierSettlementService::STAGE_EXTRA_PRINT;
        }

        if ($beforePaidAmount > 0) {
            return CashierSettlementService::STAGE_PELUNASAN;
        }

        if ($totalAmount > 0 && $newPaidAmount < $totalAmount) {
            return CashierSettlementService::STAGE_DP;
        }

        return CashierSettlementService::STAGE_FULL;
    }

    public function addExtraPrint(Transaction $transaction, array $payload, int $cashierId): Transaction
    {
        return DB::transaction(function () use ($transaction, $payload, $cashierId): Transaction {
            /** @var Transaction $lockedTransaction */
            $lockedTransaction = Transaction::query()
                ->with('items')
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureExtraPrintAllowed($lockedTransaction);

            $idempotencyKey = trim((string) ($payload['idempotency_key'] ?? ''));

            if ($idempotencyKey !== '') {
                $alreadyProcessed = Payment::query()
                    ->where('transaction_id', (int) $lockedTransaction->id)
                    ->where('meta->extra_print_idempotency_key', $idempotencyKey)
                    ->exists();

                if ($alreadyProcessed) {
                    return $lockedTransaction->refresh();
                }
            }

            /** @var AddOn $addOn */
            $addOn = AddOn::query()
                ->whereKey((int) $payload['add_on_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $packageIds = $this->packageIdsFromTransaction($lockedTransaction);

            if ($addOn->package_id !== null && ! $packageIds->contains((int) $addOn->package_id)) {
                throw ValidationException::withMessages([
                    'add_on_id' => 'Add-on tidak sesuai dengan paket transaksi ini.',
                ]);
            }

            $qty = max(1, (int) $payload['qty']);
            $maxQty = max(1, (int) $addOn->max_qty);

            if ($qty > $maxQty) {
                throw ValidationException::withMessages([
                    'qty' => sprintf('Maksimum qty untuk %s adalah %d.', (string) $addOn->name, $maxQty),
                ]);
            }

            $unitPrice = (float) $addOn->price;
            $lineTotal = $qty * $unitPrice;
            $paymentAmount = array_key_exists('paid_amount', $payload)
                ? max(0, (float) $payload['paid_amount'])
                : $lineTotal;

            if ($paymentAmount < $lineTotal) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Nominal pembayaran tidak boleh kurang dari total tambah cetak.',
                ]);
            }

            /** @var TransactionItem $line */
            $line = TransactionItem::query()->create([
                'transaction_id' => $lockedTransaction->id,
                'item_type' => 'add_on',
                'item_ref_id' => (int) $addOn->id,
                'item_name' => (string) $addOn->name,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);

            $subtotal = (float) $lockedTransaction->subtotal + $lineTotal;
            $discount = (float) $lockedTransaction->discount_amount;
            $tax = (float) $lockedTransaction->tax_amount;
            $total = max(0, $subtotal - $discount + $tax);
            $paid = (float) $lockedTransaction->paid_amount;

            $lockedTransaction->subtotal = $subtotal;
            $lockedTransaction->total_amount = $total;
            $lockedTransaction->change_amount = max(0, $paid - $total);
            $lockedTransaction->status = $paid >= $total ? TransactionStatus::Paid : TransactionStatus::Partial;
            $lockedTransaction->save();

            $updatedTransaction = $this->addPayment($lockedTransaction, [
                'method' => (string) $payload['payment_method'],
                'amount' => $paymentAmount,
                'reference_no' => $payload['reference_no'] ?? null,
                'notes' => $payload['notes'] ?? 'Tambah cetak.',
                'meta' => [
                    'type' => 'extra_print',
                    'extra_print_idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
                    'transaction_item_id' => (int) $line->id,
                    'add_on_id' => (int) $addOn->id,
                    'qty' => $qty,
                ],
            ], $cashierId);

            $this->inventoryService->deductForTransactionItems(
                $updatedTransaction,
                [$line],
                $cashierId,
                InventoryService::SOURCE_TRANSACTION_EXTRA_PRINT
            );

            $this->activityLogger->log(
                'transactions',
                'extra_print_added',
                $cashierId,
                Transaction::class,
                (int) $lockedTransaction->id,
                [
                    'message' => sprintf('Tambah cetak ditambahkan ke transaksi %s.', (string) $lockedTransaction->transaction_code),
                    'label' => (string) $lockedTransaction->transaction_code,
                    'add_on_id' => (int) $addOn->id,
                    'item_name' => (string) $addOn->name,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                    'payment_amount' => $paymentAmount,
                ],
            );

            return $updatedTransaction->refresh();
        });
    }

    private function ensureExtraPrintAllowed(Transaction $transaction): void
    {
        $status = $transaction->status instanceof TransactionStatus
            ? $transaction->status->value
            : (string) $transaction->status;

        if ($status !== TransactionStatus::Paid->value) {
            throw ValidationException::withMessages([
                'transaction' => 'Tambah cetak hanya bisa untuk transaksi yang sudah lunas.',
            ]);
        }

        $timezone = config('app.queue_timezone', 'Asia/Jakarta');
        $transactionDate = $transaction->created_at?->copy()->timezone($timezone)->toDateString();
        $today = Carbon::now($timezone)->toDateString();

        if ($transactionDate !== $today) {
            throw ValidationException::withMessages([
                'transaction' => 'Tambah cetak hanya bisa untuk transaksi hari ini.',
            ]);
        }
    }

    private function packageIdsFromTransaction(Transaction $transaction): Collection
    {
        return collect($transaction->items ?? [])
            ->filter(fn ($item): bool => in_array((string) $item->item_type, ['package', 'booking'], true) && (int) $item->item_ref_id > 0)
            ->pluck('item_ref_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
    }
}
