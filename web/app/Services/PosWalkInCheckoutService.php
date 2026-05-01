<?php

namespace App\Services;

use App\Models\Package;
use App\Models\QueueTicket;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosWalkInCheckoutService
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly QueueService $queueService,
        private readonly TransactionService $transactionService,
        private readonly InventoryService $inventoryService,
    ) {}

    public function checkout(array $payload, int $cashierId): array
    {
        if ($cashierId <= 0) {
            throw ValidationException::withMessages([
                'cashier' => 'Authenticated cashier is required.',
            ]);
        }

        return DB::transaction(function () use ($payload, $cashierId): array {
            /** @var Package $package */
            $package = Package::query()
                ->whereKey((int) $payload['package_id'])
                ->where('is_active', true)
                ->firstOrFail();

            if ($package->branch_id !== null && (int) $package->branch_id !== (int) $payload['branch_id']) {
                throw ValidationException::withMessages([
                    'package_id' => 'Paket tidak tersedia untuk cabang ini.',
                ]);
            }

            $selectedAddOns = $this->bookingService->resolveAddOnsForPackage(
                (int) $package->id,
                $payload['addons'] ?? []
            );
            $items = $this->buildTransactionItems($package, $selectedAddOns);
            $subtotal = (float) collect($items)->sum('line_total');
            $discount = max(0, (float) ($payload['discount_amount'] ?? 0));
            $tax = max(0, (float) ($payload['tax_amount'] ?? 0));
            $total = max(0, $subtotal - $discount + $tax);
            $paymentAmount = array_key_exists('paid_amount', $payload)
                ? max(0, (float) $payload['paid_amount'])
                : $total;
            $queueDate = (string) ($payload['queue_date'] ?? $this->queueTodayDate());

            /** @var QueueTicket $queueTicket */
            $queueTicket = $this->queueService->createWalkIn([
                'branch_id' => (int) $payload['branch_id'],
                'queue_date' => $queueDate,
                'customer_name' => (string) $payload['customer_name'],
                'customer_phone' => $payload['customer_phone'] ?? null,
            ]);

            /** @var Transaction $transaction */
            $transaction = $this->transactionService->create([
                'branch_id' => (int) $payload['branch_id'],
                'queue_ticket_id' => (int) $queueTicket->id,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'notes' => $payload['notes'] ?? 'POS walk-in checkout.',
                'items' => $items,
            ], $cashierId);

            if ($paymentAmount > 0) {
                $transaction = $this->transactionService->addPayment($transaction, [
                    'method' => (string) $payload['payment_method'],
                    'amount' => $paymentAmount,
                    'reference_no' => $payload['reference_no'] ?? null,
                    'notes' => 'POS walk-in payment.',
                ], $cashierId);
            }

            $this->inventoryService->deductForTransaction($transaction, $cashierId);

            return [
                'transaction' => $transaction->refresh()->load(['branch', 'queueTicket', 'items', 'payments']),
                'queue_ticket' => $queueTicket->refresh()->load(['branch', 'booking']),
            ];
        });
    }

    private function buildTransactionItems(Package $package, array $selectedAddOns): array
    {
        $items = [[
            'item_type' => 'package',
            'item_ref_id' => (int) $package->id,
            'item_name' => (string) $package->name,
            'qty' => 1,
            'unit_price' => (float) $package->base_price,
            'line_total' => (float) $package->base_price,
        ]];

        foreach ($selectedAddOns as $addOn) {
            $items[] = [
                'item_type' => 'add_on',
                'item_ref_id' => (int) $addOn['id'],
                'item_name' => (string) $addOn['name'],
                'qty' => (int) $addOn['qty'],
                'unit_price' => (float) $addOn['unit_price'],
                'line_total' => (float) $addOn['line_total'],
            ];
        }

        return $items;
    }

    private function queueTodayDate(): string
    {
        return Carbon::now(config('app.queue_timezone', 'Asia/Jakarta'))->toDateString();
    }
}
