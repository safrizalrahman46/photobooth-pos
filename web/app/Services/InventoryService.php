<?php

namespace App\Services;

use App\Models\AddOn;
use App\Models\Booking;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public const SOURCE_BOOKING_VERIFICATION = 'booking_verification';

    public const SOURCE_POS_WALK_IN_TRANSACTION = 'pos_walk_in_transaction';

    public const SOURCE_TRANSACTION_EXTRA_PRINT = 'transaction_extra_print';

    public const SOURCE_SELF_WALK_IN_TRANSACTION = 'self_walk_in_transaction';

    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function managementPayload(): array
    {
        return [
            'inventory_items' => $this->itemRows(),
            'inventory_movements' => $this->movementRows(),
        ];
    }

    public function itemRows(): array
    {
        return InventoryItem::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'unit',
                'available_stock',
                'low_stock_threshold',
                'is_active',
                'sort_order',
                'created_at',
                'updated_at',
            ])
            ->map(function (InventoryItem $item): array {
                return [
                    'id' => (int) $item->id,
                    'code' => (string) $item->code,
                    'name' => (string) $item->name,
                    'unit' => (string) ($item->unit ?? 'pcs'),
                    'available_stock' => max(0, (int) $item->available_stock),
                    'low_stock_threshold' => max(0, (int) $item->low_stock_threshold),
                    'is_active' => (bool) $item->is_active,
                    'sort_order' => (int) $item->sort_order,
                    'created_at' => $item->created_at?->toIso8601String(),
                    'updated_at' => $item->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function movementRows(int $limit = 100): array
    {
        return InventoryMovement::query()
            ->with(['inventoryItem:id,code,name,unit', 'actor:id,name'])
            ->latest()
            ->limit(max(1, min($limit, 500)))
            ->get([
                'id',
                'inventory_item_id',
                'movement_type',
                'qty',
                'stock_before',
                'stock_after',
                'source_type',
                'source_id',
                'source_ref',
                'notes',
                'moved_by',
                'created_at',
            ])
            ->map(function (InventoryMovement $movement): array {
                return [
                    'id' => (int) $movement->id,
                    'inventory_item_id' => (int) $movement->inventory_item_id,
                    'inventory_item_code' => (string) ($movement->inventoryItem?->code ?? ''),
                    'inventory_item_name' => (string) ($movement->inventoryItem?->name ?? '-'),
                    'unit' => (string) ($movement->inventoryItem?->unit ?? 'pcs'),
                    'movement_type' => (string) $movement->movement_type,
                    'qty' => (int) $movement->qty,
                    'stock_before' => (int) $movement->stock_before,
                    'stock_after' => (int) $movement->stock_after,
                    'source_type' => (string) ($movement->source_type ?? ''),
                    'source_id' => $movement->source_id ? (int) $movement->source_id : null,
                    'source_ref' => (string) ($movement->source_ref ?? ''),
                    'notes' => (string) ($movement->notes ?? ''),
                    'actor_name' => (string) ($movement->actor?->name ?? 'System'),
                    'created_at' => $movement->created_at?->toIso8601String(),
                    'created_at_text' => $movement->created_at?->format('d M Y H:i') ?? '-',
                ];
            })
            ->values()
            ->all();
    }

    public function mapAddOnInventoryItems(AddOn $addOn): array
    {
        return collect($addOn->inventoryItems ?? [])
            ->map(function (InventoryItem $item): array {
                return [
                    'inventory_item_id' => (int) $item->id,
                    'code' => (string) $item->code,
                    'name' => (string) $item->name,
                    'unit' => (string) ($item->unit ?? 'pcs'),
                    'available_stock' => max(0, (int) $item->available_stock),
                    'low_stock_threshold' => max(0, (int) $item->low_stock_threshold),
                    'is_active' => (bool) $item->is_active,
                    'qty_per_unit' => max(1, (int) ($item->pivot?->qty_per_unit ?? 1)),
                ];
            })
            ->values()
            ->all();
    }

    public function mapPackageInventoryItems(Package $package): array
    {
        return collect($package->inventoryItems ?? [])
            ->map(function (InventoryItem $item): array {
                return [
                    'inventory_item_id' => (int) $item->id,
                    'code' => (string) $item->code,
                    'name' => (string) $item->name,
                    'unit' => (string) ($item->unit ?? 'pcs'),
                    'qty_per_booking' => max(1, (int) ($item->pivot?->qty_per_booking ?? 1)),
                ];
            })
            ->values()
            ->all();
    }

    public function effectiveAvailableStock(array $inventoryItems): ?int
    {
        if ($inventoryItems === []) {
            return null;
        }

        $quantities = collect($inventoryItems)
            ->map(function (array $item): int {
                if (! (bool) ($item['is_active'] ?? false)) {
                    return 0;
                }

                $availableStock = max(0, (int) ($item['available_stock'] ?? 0));
                $qtyPerUnit = max(1, (int) ($item['qty_per_unit'] ?? 1));

                return intdiv($availableStock, $qtyPerUnit);
            })
            ->values();

        return $quantities->isEmpty() ? null : (int) $quantities->min();
    }

    public function effectiveStockTone(array $inventoryItems, ?int $effectiveStock): array
    {
        if ($effectiveStock === null) {
            return ['status' => 'untracked', 'label' => 'Not mapped'];
        }

        if ($effectiveStock <= 0) {
            return ['status' => 'out', 'label' => 'Out'];
        }

        $hasLowComponent = collect($inventoryItems)->contains(function (array $item): bool {
            return (bool) ($item['is_active'] ?? false)
                && max(0, (int) ($item['available_stock'] ?? 0)) <= max(0, (int) ($item['low_stock_threshold'] ?? 0));
        });

        if ($hasLowComponent) {
            return ['status' => 'low', 'label' => 'Low'];
        }

        return ['status' => 'ready', 'label' => 'Ready'];
    }

    public function syncAddOnConsumptions(AddOn $addOn, array $rows): void
    {
        $syncData = $this->normalizeConsumptionRows($rows, 'qty_per_unit');

        $addOn->inventoryItems()->sync($syncData);
    }

    public function syncPackageConsumptions(Package $package, array $rows): void
    {
        $syncData = $this->normalizeConsumptionRows($rows, 'qty_per_booking');

        $package->inventoryItems()->sync($syncData);
    }

    public function recordMovement(InventoryItem $inventoryItem, array $payload, ?int $actorId = null): InventoryItem
    {
        return DB::transaction(function () use ($inventoryItem, $payload, $actorId): InventoryItem {
            /** @var InventoryItem $lockedItem */
            $lockedItem = InventoryItem::query()->whereKey($inventoryItem->id)->lockForUpdate()->firstOrFail();

            $movementType = strtolower(trim((string) ($payload['movement_type'] ?? '')));
            $qty = max(1, (int) ($payload['qty'] ?? 0));
            $beforeStock = max(0, (int) $lockedItem->available_stock);
            $afterStock = $beforeStock;

            if ($movementType === 'in') {
                $afterStock = $beforeStock + $qty;
            } elseif ($movementType === 'out') {
                if ($qty > $beforeStock) {
                    throw ValidationException::withMessages([
                        'qty' => 'Stok tidak mencukupi untuk barang keluar.',
                    ]);
                }

                $afterStock = $beforeStock - $qty;
            } else {
                throw ValidationException::withMessages([
                    'movement_type' => 'Tipe pergerakan stok tidak valid.',
                ]);
            }

            $lockedItem->update([
                'available_stock' => $afterStock,
            ]);

            $lockedItem->movements()->create([
                'movement_type' => $movementType,
                'qty' => $qty,
                'stock_before' => $beforeStock,
                'stock_after' => $afterStock,
                'source_type' => $payload['source_type'] ?? null,
                'source_id' => $payload['source_id'] ?? null,
                'source_ref' => $payload['source_ref'] ?? null,
                'notes' => trim((string) ($payload['notes'] ?? '')) ?: null,
                'moved_by' => $actorId,
            ]);

            $this->activityLogger->log(
                'inventory',
                'movement_recorded',
                $actorId,
                InventoryItem::class,
                (int) $lockedItem->id,
                [
                    'message' => sprintf('Pergerakan stok %s untuk %s dicatat.', $movementType, (string) $lockedItem->name),
                    'label' => (string) $lockedItem->code,
                    'item_name' => (string) $lockedItem->name,
                    'movement_type' => $movementType,
                    'qty' => $qty,
                    'stock_before' => $beforeStock,
                    'stock_after' => $afterStock,
                    'source_type' => $payload['source_type'] ?? null,
                    'source_ref' => $payload['source_ref'] ?? null,
                    'notes' => trim((string) ($payload['notes'] ?? '')) ?: null,
                ],
            );

            return $lockedItem->refresh();
        });
    }

    public function deductForVerifiedBooking(Booking $booking, ?int $actorId = null): void
    {
        DB::transaction(function () use ($booking, $actorId): void {
            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $alreadyDeducted = InventoryMovement::query()
                ->where('source_type', self::SOURCE_BOOKING_VERIFICATION)
                ->where('source_id', (int) $lockedBooking->id)
                ->exists();

            if ($alreadyDeducted) {
                return;
            }

            $lockedBooking->load([
                'package.inventoryItems',
                'addOns.inventoryItems',
            ]);

            $requirements = $this->bookingRequirements($lockedBooking);

            if ($requirements->isEmpty()) {
                return;
            }

            $items = InventoryItem::query()
                ->whereIn('id', $requirements->keys()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $shortages = [];

            foreach ($requirements as $itemId => $requiredQty) {
                /** @var InventoryItem|null $item */
                $item = $items->get((int) $itemId);

                if (! $item || ! $item->is_active) {
                    $shortages[] = 'Barang stok tidak aktif/tidak ditemukan';

                    continue;
                }

                $available = max(0, (int) $item->available_stock);

                if ($requiredQty > $available) {
                    $shortages[] = sprintf(
                        '%s butuh %d %s, tersedia %d %s',
                        (string) $item->name,
                        (int) $requiredQty,
                        (string) $item->unit,
                        $available,
                        (string) $item->unit
                    );
                }
            }

            if ($shortages !== []) {
                throw ValidationException::withMessages([
                    'inventory' => 'Stok tidak mencukupi: '.implode('; ', $shortages),
                ]);
            }

            foreach ($requirements as $itemId => $requiredQty) {
                /** @var InventoryItem $item */
                $item = $items->get((int) $itemId);
                $beforeStock = max(0, (int) $item->available_stock);
                $afterStock = $beforeStock - (int) $requiredQty;

                $item->update([
                    'available_stock' => $afterStock,
                ]);

                $item->movements()->create([
                    'movement_type' => 'out',
                    'qty' => (int) $requiredQty,
                    'stock_before' => $beforeStock,
                    'stock_after' => $afterStock,
                    'source_type' => self::SOURCE_BOOKING_VERIFICATION,
                    'source_id' => (int) $lockedBooking->id,
                    'source_ref' => (string) $lockedBooking->booking_code,
                    'notes' => 'Auto deduction from verified booking.',
                    'moved_by' => $actorId,
                ]);
            }

            $this->activityLogger->log(
                'inventory',
                'booking_deducted',
                $actorId,
                Booking::class,
                (int) $lockedBooking->id,
                [
                    'message' => sprintf('Stok booking %s berhasil dipotong.', (string) $lockedBooking->booking_code),
                    'label' => (string) $lockedBooking->booking_code,
                    'source_type' => self::SOURCE_BOOKING_VERIFICATION,
                    'items' => $requirements->map(function (int $qty, int|string $itemId) use ($items): array {
                        /** @var InventoryItem|null $item */
                        $item = $items->get((int) $itemId);

                        return [
                            'inventory_item_id' => (int) $itemId,
                            'code' => (string) ($item?->code ?? ''),
                            'name' => (string) ($item?->name ?? '-'),
                            'qty' => $qty,
                            'unit' => (string) ($item?->unit ?? 'pcs'),
                        ];
                    })->values()->all(),
                ],
            );
        });
    }

    public function deductForTransaction(Transaction $transaction, ?int $actorId = null, string $sourceType = self::SOURCE_POS_WALK_IN_TRANSACTION): void
    {
        DB::transaction(function () use ($transaction, $actorId, $sourceType): void {
            /** @var Transaction $lockedTransaction */
            $lockedTransaction = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            $alreadyDeducted = InventoryMovement::query()
                ->where('source_type', $sourceType)
                ->where('source_id', (int) $lockedTransaction->id)
                ->exists();

            if ($alreadyDeducted) {
                return;
            }

            $lockedTransaction->load(['items']);
            $requirements = $this->transactionRequirements($lockedTransaction);

            if ($requirements->isEmpty()) {
                return;
            }

            $items = InventoryItem::query()
                ->whereIn('id', $requirements->keys()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $shortages = [];

            foreach ($requirements as $itemId => $requiredQty) {
                /** @var InventoryItem|null $item */
                $item = $items->get((int) $itemId);

                if (! $item || ! $item->is_active) {
                    $shortages[] = 'Barang stok tidak aktif/tidak ditemukan';

                    continue;
                }

                $available = max(0, (int) $item->available_stock);

                if ($requiredQty > $available) {
                    $shortages[] = sprintf(
                        '%s butuh %d %s, tersedia %d %s',
                        (string) $item->name,
                        (int) $requiredQty,
                        (string) $item->unit,
                        $available,
                        (string) $item->unit
                    );
                }
            }

            if ($shortages !== []) {
                throw ValidationException::withMessages([
                    'inventory' => 'Stok tidak mencukupi: '.implode('; ', $shortages),
                ]);
            }

            foreach ($requirements as $itemId => $requiredQty) {
                /** @var InventoryItem $item */
                $item = $items->get((int) $itemId);
                $beforeStock = max(0, (int) $item->available_stock);
                $afterStock = $beforeStock - (int) $requiredQty;

                $item->update([
                    'available_stock' => $afterStock,
                ]);

                $item->movements()->create([
                    'movement_type' => 'out',
                    'qty' => (int) $requiredQty,
                    'stock_before' => $beforeStock,
                    'stock_after' => $afterStock,
                    'source_type' => $sourceType,
                    'source_id' => (int) $lockedTransaction->id,
                    'source_ref' => (string) $lockedTransaction->transaction_code,
                    'notes' => 'Auto deduction from POS walk-in transaction.',
                    'moved_by' => $actorId,
                ]);
            }

            $this->activityLogger->log(
                'inventory',
                'transaction_deducted',
                $actorId,
                Transaction::class,
                (int) $lockedTransaction->id,
                [
                    'message' => sprintf('Stok transaksi %s berhasil dipotong.', (string) $lockedTransaction->transaction_code),
                    'label' => (string) $lockedTransaction->transaction_code,
                    'source_type' => $sourceType,
                    'items' => $requirements->map(function (int $qty, int|string $itemId) use ($items): array {
                        /** @var InventoryItem|null $item */
                        $item = $items->get((int) $itemId);

                        return [
                            'inventory_item_id' => (int) $itemId,
                            'code' => (string) ($item?->code ?? ''),
                            'name' => (string) ($item?->name ?? '-'),
                            'qty' => $qty,
                            'unit' => (string) ($item?->unit ?? 'pcs'),
                        ];
                    })->values()->all(),
                ],
            );
        });
    }

    public function deductForTransactionItems(Transaction $transaction, iterable $transactionItems, ?int $actorId = null, string $sourceType = self::SOURCE_TRANSACTION_EXTRA_PRINT): void
    {
        DB::transaction(function () use ($transaction, $transactionItems, $actorId, $sourceType): void {
            /** @var Transaction $lockedTransaction */
            $lockedTransaction = Transaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $lines = collect($transactionItems)
                ->map(fn ($line): int => (int) ($line instanceof TransactionItem ? $line->id : (is_array($line) ? ($line['id'] ?? 0) : 0)))
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            if ($lines->isEmpty()) {
                return;
            }

            /** @var Collection<int, TransactionItem> $lockedLines */
            $lockedLines = TransactionItem::query()
                ->where('transaction_id', (int) $lockedTransaction->id)
                ->whereIn('id', $lines->all())
                ->lockForUpdate()
                ->get();

            if ($lockedLines->isEmpty()) {
                return;
            }

            $deductedLines = [];
            $deductedItems = collect();

            foreach ($lockedLines as $line) {
                $alreadyDeducted = InventoryMovement::query()
                    ->where('source_type', $sourceType)
                    ->where('source_id', (int) $line->id)
                    ->exists();

                if ($alreadyDeducted) {
                    continue;
                }

                $requirements = $this->transactionItemRequirements($line);

                if ($requirements->isEmpty()) {
                    continue;
                }

                $items = InventoryItem::query()
                    ->whereIn('id', $requirements->keys()->all())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $shortages = [];

                foreach ($requirements as $itemId => $requiredQty) {
                    /** @var InventoryItem|null $item */
                    $item = $items->get((int) $itemId);

                    if (! $item || ! $item->is_active) {
                        $shortages[] = 'Barang stok tidak aktif/tidak ditemukan';

                        continue;
                    }

                    $available = max(0, (int) $item->available_stock);

                    if ($requiredQty > $available) {
                        $shortages[] = sprintf(
                            '%s butuh %d %s, tersedia %d %s',
                            (string) $item->name,
                            (int) $requiredQty,
                            (string) $item->unit,
                            $available,
                            (string) $item->unit
                        );
                    }
                }

                if ($shortages !== []) {
                    throw ValidationException::withMessages([
                        'inventory' => 'Stok tidak mencukupi: '.implode('; ', $shortages),
                    ]);
                }

                foreach ($requirements as $itemId => $requiredQty) {
                    /** @var InventoryItem $item */
                    $item = $items->get((int) $itemId);
                    $beforeStock = max(0, (int) $item->available_stock);
                    $afterStock = $beforeStock - (int) $requiredQty;

                    $item->update([
                        'available_stock' => $afterStock,
                    ]);

                    $item->movements()->create([
                        'movement_type' => 'out',
                        'qty' => (int) $requiredQty,
                        'stock_before' => $beforeStock,
                        'stock_after' => $afterStock,
                        'source_type' => $sourceType,
                        'source_id' => (int) $line->id,
                        'source_ref' => (string) $lockedTransaction->transaction_code,
                        'notes' => 'Auto deduction from transaction extra print item.',
                        'moved_by' => $actorId,
                    ]);

                    $deductedItems->push([
                        'inventory_item_id' => (int) $itemId,
                        'code' => (string) $item->code,
                        'name' => (string) $item->name,
                        'qty' => (int) $requiredQty,
                        'unit' => (string) ($item->unit ?? 'pcs'),
                        'transaction_item_id' => (int) $line->id,
                    ]);
                }

                $deductedLines[] = (int) $line->id;
            }

            if ($deductedLines === []) {
                return;
            }

            $this->activityLogger->log(
                'inventory',
                'transaction_items_deducted',
                $actorId,
                Transaction::class,
                (int) $lockedTransaction->id,
                [
                    'message' => sprintf('Stok tambah cetak transaksi %s berhasil dipotong.', (string) $lockedTransaction->transaction_code),
                    'label' => (string) $lockedTransaction->transaction_code,
                    'source_type' => $sourceType,
                    'transaction_item_ids' => $deductedLines,
                    'items' => $deductedItems->values()->all(),
                ],
            );
        });
    }

    private function normalizeConsumptionRows(array $rows, string $qtyColumn): array
    {
        return collect($rows)
            ->filter(fn ($row): bool => is_array($row))
            ->map(function (array $row) use ($qtyColumn): array {
                $inventoryItemId = (int) ($row['inventory_item_id'] ?? $row['id'] ?? 0);
                $qty = (int) ($row[$qtyColumn] ?? $row['qty'] ?? 0);

                return [
                    'inventory_item_id' => $inventoryItemId,
                    $qtyColumn => max(1, $qty),
                ];
            })
            ->filter(fn (array $row): bool => $row['inventory_item_id'] > 0)
            ->groupBy('inventory_item_id')
            ->mapWithKeys(function (Collection $group, int|string $inventoryItemId) use ($qtyColumn): array {
                return [
                    (int) $inventoryItemId => [
                        $qtyColumn => (int) $group->sum($qtyColumn),
                    ],
                ];
            })
            ->all();
    }

    private function bookingRequirements(Booking $booking): Collection
    {
        $requirements = collect();

        foreach ($booking->package?->inventoryItems ?? [] as $item) {
            $qty = max(0, (int) ($item->pivot?->qty_per_booking ?? 0));

            if ($qty > 0) {
                $requirements->put((int) $item->id, (int) $requirements->get((int) $item->id, 0) + $qty);
            }
        }

        foreach ($booking->addOns ?? [] as $addOn) {
            $addOnQty = max(0, (int) ($addOn->pivot?->qty ?? 0));

            if ($addOnQty <= 0) {
                continue;
            }

            foreach ($addOn->inventoryItems ?? [] as $item) {
                $qtyPerUnit = max(0, (int) ($item->pivot?->qty_per_unit ?? 0));
                $requiredQty = $addOnQty * $qtyPerUnit;

                if ($requiredQty > 0) {
                    $requirements->put((int) $item->id, (int) $requirements->get((int) $item->id, 0) + $requiredQty);
                }
            }
        }

        return $requirements;
    }

    private function transactionRequirements(Transaction $transaction): Collection
    {
        $requirements = collect();
        $packageIds = collect($transaction->items ?? [])
            ->filter(fn ($item): bool => in_array((string) $item->item_type, ['package', 'booking'], true) && (int) $item->item_ref_id > 0)
            ->pluck('item_ref_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $addOnIds = collect($transaction->items ?? [])
            ->filter(fn ($item): bool => (string) $item->item_type === 'add_on' && (int) $item->item_ref_id > 0)
            ->pluck('item_ref_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $packages = Package::query()
            ->with('inventoryItems')
            ->whereIn('id', $packageIds->all())
            ->get()
            ->keyBy('id');
        $addOns = AddOn::query()
            ->with('inventoryItems')
            ->whereIn('id', $addOnIds->all())
            ->get()
            ->keyBy('id');

        foreach ($transaction->items ?? [] as $line) {
            $lineType = (string) $line->item_type;
            $lineQty = max(1, (int) $line->qty);

            if (in_array($lineType, ['package', 'booking'], true)) {
                /** @var Package|null $package */
                $package = $packages->get((int) $line->item_ref_id);

                foreach ($package?->inventoryItems ?? [] as $item) {
                    $qty = $lineQty * max(0, (int) ($item->pivot?->qty_per_booking ?? 0));

                    if ($qty > 0) {
                        $requirements->put((int) $item->id, (int) $requirements->get((int) $item->id, 0) + $qty);
                    }
                }
            }

            if ($lineType === 'add_on') {
                /** @var AddOn|null $addOn */
                $addOn = $addOns->get((int) $line->item_ref_id);

                foreach ($addOn?->inventoryItems ?? [] as $item) {
                    $qty = $lineQty * max(0, (int) ($item->pivot?->qty_per_unit ?? 0));

                    if ($qty > 0) {
                        $requirements->put((int) $item->id, (int) $requirements->get((int) $item->id, 0) + $qty);
                    }
                }
            }
        }

        return $requirements;
    }

    private function transactionItemRequirements(TransactionItem $line): Collection
    {
        $requirements = collect();
        $lineType = (string) $line->item_type;
        $lineQty = max(1, (int) $line->qty);

        if (in_array($lineType, ['package', 'booking'], true)) {
            /** @var Package|null $package */
            $package = Package::query()
                ->with('inventoryItems')
                ->whereKey((int) $line->item_ref_id)
                ->first();

            foreach ($package?->inventoryItems ?? [] as $item) {
                $qty = $lineQty * max(0, (int) ($item->pivot?->qty_per_booking ?? 0));

                if ($qty > 0) {
                    $requirements->put((int) $item->id, (int) $requirements->get((int) $item->id, 0) + $qty);
                }
            }
        }

        if ($lineType === 'add_on') {
            /** @var AddOn|null $addOn */
            $addOn = AddOn::query()
                ->with('inventoryItems')
                ->whereKey((int) $line->item_ref_id)
                ->first();

            foreach ($addOn?->inventoryItems ?? [] as $item) {
                $qty = $lineQty * max(0, (int) ($item->pivot?->qty_per_unit ?? 0));

                if ($qty > 0) {
                    $requirements->put((int) $item->id, (int) $requirements->get((int) $item->id, 0) + $qty);
                }
            }
        }

        return $requirements;
    }
}
