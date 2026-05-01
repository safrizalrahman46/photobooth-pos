<?php

namespace App\Http\Resources;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddOnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $inventoryItems = collect($this->whenLoaded('inventoryItems', fn () => $this->inventoryItems, []))
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
            ->values();

        $effectiveStock = $this->effectiveAvailableStock($inventoryItems->all());
        $stockTone = $this->effectiveStockTone($inventoryItems->all(), $effectiveStock);

        return [
            'id' => (int) $this->id,
            'package_id' => $this->package_id ? (int) $this->package_id : null,
            'code' => (string) $this->code,
            'name' => (string) $this->name,
            'description' => (string) ($this->description ?? ''),
            'price' => (float) $this->price,
            'max_qty' => max(1, (int) $this->max_qty),
            'is_physical' => (bool) $this->is_physical,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'inventory_items' => $inventoryItems,
            'effective_available_stock' => $effectiveStock,
            'effective_stock_status' => $stockTone['status'],
            'effective_stock_label' => $stockTone['label'],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function effectiveAvailableStock(array $inventoryItems): ?int
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

    private function effectiveStockTone(array $inventoryItems, ?int $effectiveStock): array
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
}
