<?php

namespace App\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\TransactionStatus;
use App\Models\AddOn;
use App\Models\Booking;
use App\Models\DesignCatalog;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AdminBookingManagementService
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly TransactionService $transactionService,
    ) {}

    public function create(array $payload): Booking
    {
        [$package, $design, $startAt, $endAt, $selectedAddOns] = $this->buildBookingContext($payload);

        $this->assertNoConflict((int) $payload['branch_id'], $startAt, $endAt);

        try {
            $booking = $this->bookingService->create(array_merge($payload, [
                'source' => BookingSource::Admin,
            ]));
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages([
                'booking_time' => $exception->getMessage(),
            ]);
        }

        DB::transaction(function () use ($booking, $package, $selectedAddOns): void {
            $this->syncBookingAddOns($booking, $selectedAddOns);

            $booking->update([
                'total_amount' => $this->calculateBookingTotal($package, $selectedAddOns),
            ]);

            $this->syncBookingTransactionItems($booking, $package, $selectedAddOns);
        });

        return $booking->refresh();
    }

    public function update(Booking $booking, array $payload): Booking
    {
        [$package, $design, $startAt, $endAt, $selectedAddOns] = $this->buildBookingContext($payload);

        $this->assertNoConflict((int) $payload['branch_id'], $startAt, $endAt, (int) $booking->id);

        $nextTotal = $this->calculateBookingTotal($package, $selectedAddOns);

        DB::transaction(function () use ($booking, $payload, $package, $design, $startAt, $endAt, $selectedAddOns, $nextTotal): void {
            $booking->update([
                'branch_id' => (int) $payload['branch_id'],
                'package_id' => (int) $payload['package_id'],
                'design_catalog_id' => $design?->id,
                'customer_name' => (string) $payload['customer_name'],
                'customer_phone' => (string) $payload['customer_phone'],
                'customer_email' => $payload['customer_email'] ?: null,
                'booking_date' => $startAt->toDateString(),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'total_amount' => $nextTotal,
                'notes' => $payload['notes'] ?: null,
            ]);

            $this->syncBookingAddOns($booking, $selectedAddOns);
            $this->syncBookingTransactionItems($booking, $package, $selectedAddOns);
        });

        return $booking->refresh();
    }

    public function delete(Booking $booking): void
    {
        $booking->delete();
    }

    public function confirm(Booking $booking, ?int $actorId = null, ?string $reason = null): Booking
    {
        $status = $booking->status instanceof BookingStatus
            ? $booking->status
            : BookingStatus::from((string) $booking->status);

        if (in_array($status->value, [BookingStatus::Cancelled->value, BookingStatus::Done->value], true)) {
            throw ValidationException::withMessages([
                'status' => 'Booking with current status cannot be confirmed.',
            ]);
        }

        if ($status === BookingStatus::Pending) {
            $this->bookingService->updateStatus(
                $booking,
                BookingStatus::Confirmed,
                $actorId,
                $reason ?: 'Confirmed from owner dashboard'
            );

            $booking->update([
                'approved_by' => $actorId ?: $booking->approved_by,
                'approved_at' => now(),
            ]);
        }

        return $booking->refresh();
    }

    public function confirmPayment(Booking $booking, array $payload, int $cashierId): Transaction
    {
        if ($cashierId <= 0) {
            throw ValidationException::withMessages([
                'cashier' => 'Authenticated cashier is required to confirm payment.',
            ]);
        }

        $bookingStatus = $booking->status instanceof BookingStatus
            ? $booking->status->value
            : (string) $booking->status;

        if ($bookingStatus === BookingStatus::Pending->value) {
            throw ValidationException::withMessages([
                'booking' => 'Booking belum diverifikasi. Konfirmasi booking terlebih dahulu.',
            ]);
        }

        if (in_array($bookingStatus, [BookingStatus::Cancelled->value, BookingStatus::Done->value], true)) {
            throw ValidationException::withMessages([
                'booking' => 'Status booking saat ini tidak dapat diproses pembayaran.',
            ]);
        }

        $booking->loadMissing(['package', 'addOns', 'transaction.items', 'transaction.payments']);

        $beforeStatus = $booking->status instanceof BookingStatus
            ? $booking->status->value
            : (string) $booking->status;

        $transaction = $this->ensureBookingTransaction($booking, $cashierId);

        $remaining = max((float) $transaction->total_amount - (float) $transaction->paid_amount, 0);
        $amount = (float) ($payload['amount'] ?? $remaining);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        $updatedTransaction = $this->transactionService->addPayment($transaction, [
            'method' => $payload['method'],
            'amount' => $amount,
            'reference_no' => $payload['reference_no'] ?? null,
            'notes' => $payload['notes'] ?? 'Payment confirmed from owner dashboard',
        ], $cashierId);

        if (
            $updatedTransaction->status === TransactionStatus::Paid
            && ! in_array($beforeStatus, [BookingStatus::Paid->value, BookingStatus::Done->value], true)
        ) {
            $this->bookingService->updateStatus(
                $booking,
                BookingStatus::Paid,
                $cashierId,
                'Payment confirmed from owner dashboard'
            );
        }

        return $updatedTransaction;
    }

    private function buildBookingContext(array $payload): array
    {
        $package = Package::query()->findOrFail((int) $payload['package_id']);
        $design = ! empty($payload['design_catalog_id'])
            ? DesignCatalog::query()->find((int) $payload['design_catalog_id'])
            : null;

        $startAt = Carbon::parse($payload['booking_date'] . ' ' . $payload['booking_time']);
        $endAt = $startAt->copy()->addMinutes((int) $package->duration_minutes);

        if ($package->branch_id !== null && (int) $package->branch_id !== (int) $payload['branch_id']) {
            throw ValidationException::withMessages([
                'package_id' => 'Package is not available for selected branch.',
            ]);
        }

        if ($design && (int) $design->package_id !== (int) $package->id) {
            throw ValidationException::withMessages([
                'design_catalog_id' => 'Design is not valid for selected package.',
            ]);
        }

        $selectedAddOns = $this->resolveSelectedAddOns($payload, $package);

        return [$package, $design, $startAt, $endAt, $selectedAddOns];
    }

    private function assertNoConflict(int $branchId, Carbon $startAt, Carbon $endAt, ?int $exceptBookingId = null): void
    {
        $conflictExists = Booking::query()
            ->where('branch_id', $branchId)
            ->whereIn('status', BookingStatus::activeStatuses())
            ->when($exceptBookingId !== null, fn ($query) => $query->whereKeyNot($exceptBookingId))
            ->where(function ($query) use ($startAt, $endAt): void {
                $query->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt);
            })
            ->exists();

        if ($conflictExists) {
            throw ValidationException::withMessages([
                'booking_time' => 'Selected time slot is not available. Please choose another time.',
            ]);
        }
    }

    private function ensureBookingTransaction(Booking $booking, int $cashierId): Transaction
    {
        $booking->loadMissing(['package', 'addOns', 'transaction']);

        if ($booking->transaction) {
            return $booking->transaction;
        }

        $package = $booking->package;

        if (! $package) {
            throw ValidationException::withMessages([
                'booking' => 'Booking package not found.',
            ]);
        }

        $selectedAddOns = $booking->addOns
            ->map(function (AddOn $addOn): array {
                $qty = (int) ($addOn->pivot?->qty ?? 1);
                $unitPrice = (float) ($addOn->pivot?->unit_price ?? $addOn->price);

                return [
                    'id' => (int) $addOn->id,
                    'name' => (string) $addOn->name,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => (float) ($addOn->pivot?->line_total ?? ($qty * $unitPrice)),
                ];
            })
            ->values()
            ->all();

        $items = $this->buildBookingTransactionItems($booking, $package, $selectedAddOns);

        $createdTransaction = $this->transactionService->create([
            'branch_id' => (int) $booking->branch_id,
            'booking_id' => (int) $booking->id,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'notes' => 'Auto-generated from booking payment confirmation.',
            'items' => $items,
        ], $cashierId);

        $initialPaid = min(
            max((float) $booking->paid_amount, 0),
            max((float) $createdTransaction->total_amount, 0)
        );

        if ($initialPaid <= 0) {
            return $createdTransaction;
        }

        $initialStatus = $initialPaid < (float) $createdTransaction->total_amount
            ? TransactionStatus::Partial
            : TransactionStatus::Paid;

        $createdTransaction->update([
            'paid_amount' => $initialPaid,
            'change_amount' => max($initialPaid - (float) $createdTransaction->total_amount, 0),
            'status' => $initialStatus,
            'paid_at' => $booking->created_at ?? now(),
        ]);

        return $createdTransaction->refresh();
    }

    private function resolveSelectedAddOns(array $payload, Package $package): array
    {
        $requested = collect($payload['add_ons'] ?? [])
            ->filter(fn ($row): bool => is_array($row))
            ->map(function (array $row): array {
                return [
                    'add_on_id' => (int) ($row['add_on_id'] ?? 0),
                    'qty' => (int) ($row['qty'] ?? 0),
                ];
            })
            ->filter(fn (array $row): bool => $row['add_on_id'] > 0 && $row['qty'] > 0)
            ->groupBy('add_on_id')
            ->map(function (Collection $group, int|string $addOnId): array {
                return [
                    'add_on_id' => (int) $addOnId,
                    'qty' => (int) $group->sum('qty'),
                ];
            })
            ->values();

        if ($requested->isEmpty()) {
            return [];
        }

        $ids = $requested->pluck('add_on_id')->all();

        $addOns = AddOn::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get(['id', 'package_id', 'name', 'price', 'max_qty'])
            ->keyBy('id');

        if ($addOns->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'add_ons' => 'One or more selected add-ons are not available.',
            ]);
        }

        $selected = [];

        foreach ($requested as $item) {
            $addOn = $addOns->get((int) $item['add_on_id']);

            if (! $addOn) {
                throw ValidationException::withMessages([
                    'add_ons' => 'One or more selected add-ons are not available.',
                ]);
            }

            if ($addOn->package_id !== null && (int) $addOn->package_id !== (int) $package->id) {
                throw ValidationException::withMessages([
                    'add_ons' => 'Selected add-on is not valid for selected package.',
                ]);
            }

            $unitPrice = (float) $addOn->price;
            $qty = (int) $item['qty'];
            $maxQty = max(1, (int) $addOn->max_qty);

            if ($qty > $maxQty) {
                throw ValidationException::withMessages([
                    'add_ons' => sprintf('Maksimum qty untuk %s adalah %d.', (string) $addOn->name, $maxQty),
                ]);
            }

            $selected[] = [
                'id' => (int) $addOn->id,
                'name' => (string) $addOn->name,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $qty * $unitPrice,
            ];
        }

        return $selected;
    }

    private function calculateBookingTotal(Package $package, array $selectedAddOns): float
    {
        $addOnTotal = (float) collect($selectedAddOns)->sum('line_total');

        return (float) $package->base_price + $addOnTotal;
    }

    private function syncBookingAddOns(Booking $booking, array $selectedAddOns): void
    {
        $syncData = [];

        foreach ($selectedAddOns as $item) {
            $syncData[(int) $item['id']] = [
                'qty' => (int) $item['qty'],
                'unit_price' => (float) $item['unit_price'],
                'line_total' => (float) $item['line_total'],
            ];
        }

        $booking->addOns()->sync($syncData);
    }

    private function buildBookingTransactionItems(Booking $booking, Package $package, array $selectedAddOns): array
    {
        $packagePrice = (float) $package->base_price;

        $items = [
            [
                'item_type' => 'booking',
                'item_ref_id' => (int) $booking->package_id,
                'item_name' => (string) ($package->name ?? 'Booking Package'),
                'qty' => 1,
                'unit_price' => $packagePrice,
                'line_total' => $packagePrice,
            ],
        ];

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

    private function syncBookingTransactionItems(Booking $booking, Package $package, array $selectedAddOns): void
    {
        $booking->loadMissing(['transaction', 'transaction.items']);

        if (! $booking->transaction) {
            return;
        }

        $transaction = $booking->transaction;

        $items = $this->buildBookingTransactionItems($booking, $package, $selectedAddOns);

        $transaction->items()
            ->whereIn('item_type', ['booking', 'package', 'main', 'add_on'])
            ->delete();

        foreach ($items as $item) {
            TransactionItem::query()->create([
                'transaction_id' => $transaction->id,
                'item_type' => (string) $item['item_type'],
                'item_ref_id' => $item['item_ref_id'] ?? null,
                'item_name' => (string) $item['item_name'],
                'qty' => (int) $item['qty'],
                'unit_price' => (float) $item['unit_price'],
                'line_total' => (float) $item['line_total'],
            ]);
        }

        $subtotal = (float) collect($items)->sum('line_total');
        $discount = (float) $transaction->discount_amount;
        $tax = (float) $transaction->tax_amount;
        $total = max(0, $subtotal - $discount + $tax);
        $paid = (float) $transaction->paid_amount;

        $status = match (true) {
            $paid <= 0 => TransactionStatus::Unpaid,
            $paid < $total => TransactionStatus::Partial,
            default => TransactionStatus::Paid,
        };

        $transaction->update([
            'subtotal' => $subtotal,
            'total_amount' => $total,
            'change_amount' => max($paid - $total, 0),
            'status' => $status,
        ]);

        $booking->update([
            'total_amount' => $total,
            'paid_amount' => max((float) $booking->paid_amount, $paid),
        ]);
    }
}
