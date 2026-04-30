<?php

namespace App\Services;

use App\Models\AddOn;
use App\Models\Booking;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public const SOURCE_BOOKING_VERIFICATION = 'booking_verification';

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
}
