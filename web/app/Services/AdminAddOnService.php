<?php

namespace App\Services;

use App\Models\AddOn;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminAddOnService
{
    public function create(array $payload): AddOn
    {
        $isPhysical = (bool) ($payload['is_physical'] ?? false);

        return AddOn::query()->create([
            'package_id' => $payload['package_id'] ?? null,
            'code' => trim((string) ($payload['code'] ?? '')) ?: $this->nextCode(),
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'price' => $payload['price'],
            'max_qty' => $payload['max_qty'],
            'is_physical' => $isPhysical,
            'available_stock' => $isPhysical ? max(0, (int) ($payload['available_stock'] ?? 0)) : 0,
            'low_stock_threshold' => $isPhysical ? max(0, (int) ($payload['low_stock_threshold'] ?? 0)) : 0,
            'is_active' => $payload['is_active'] ?? true,
            'sort_order' => $payload['sort_order'] ?? 0,
        ]);
    }

    public function update(AddOn $addOn, array $payload): AddOn
    {
        $codeInput = trim((string) ($payload['code'] ?? ''));
        $isPhysical = array_key_exists('is_physical', $payload)
            ? (bool) $payload['is_physical']
            : (bool) $addOn->is_physical;

        $addOn->update([
            'package_id' => array_key_exists('package_id', $payload) ? $payload['package_id'] : $addOn->package_id,
            'code' => $codeInput !== '' ? $codeInput : $addOn->code,
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'price' => $payload['price'],
            'max_qty' => $payload['max_qty'],
            'is_physical' => $isPhysical,
            'available_stock' => $isPhysical
                ? (array_key_exists('available_stock', $payload)
                    ? max(0, (int) $payload['available_stock'])
                    : (int) $addOn->available_stock)
                : 0,
            'low_stock_threshold' => $isPhysical
                ? (array_key_exists('low_stock_threshold', $payload)
                    ? max(0, (int) $payload['low_stock_threshold'])
                    : (int) $addOn->low_stock_threshold)
                : 0,
            'is_active' => $payload['is_active'] ?? $addOn->is_active,
            'sort_order' => $payload['sort_order'] ?? $addOn->sort_order,
        ]);

        return $addOn->refresh();
    }

    public function recordStockMovement(AddOn $addOn, array $payload, ?int $actorId = null): AddOn
    {
        return DB::transaction(function () use ($addOn, $payload, $actorId): AddOn {
            /** @var AddOn $lockedAddOn */
            $lockedAddOn = AddOn::query()->whereKey($addOn->id)->lockForUpdate()->firstOrFail();

            if (! $lockedAddOn->is_physical) {
                throw ValidationException::withMessages([
                    'add_on' => 'Stock movement hanya berlaku untuk add-on physical.',
                ]);
            }

            $movementType = strtolower(trim((string) ($payload['movement_type'] ?? '')));
            $qty = max(1, (int) ($payload['qty'] ?? 0));
            $beforeStock = max(0, (int) $lockedAddOn->available_stock);
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

            $lockedAddOn->update([
                'available_stock' => $afterStock,
            ]);

            $lockedAddOn->stockMovements()->create([
                'movement_type' => $movementType,
                'qty' => $qty,
                'stock_before' => $beforeStock,
                'stock_after' => $afterStock,
                'notes' => trim((string) ($payload['notes'] ?? '')) ?: null,
                'moved_by' => $actorId,
            ]);

            return $lockedAddOn->refresh();
        });
    }

    public function delete(AddOn $addOn): void
    {
        $addOn->delete();
    }

    private function nextCode(): string
    {
        $cursor = ((int) AddOn::query()->max('id')) + 1;

        do {
            $candidate = sprintf('ADDON-%05d', $cursor);
            $exists = AddOn::query()->where('code', $candidate)->exists();
            $cursor++;
        } while ($exists);

        return $candidate;
    }
}
