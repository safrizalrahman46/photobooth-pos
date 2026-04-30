<?php

namespace App\Services;

use App\Models\AddOn;
use Illuminate\Support\Facades\DB;

class AdminAddOnService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function create(array $payload): AddOn
    {
        $isPhysical = (bool) ($payload['is_physical'] ?? false);

        return DB::transaction(function () use ($payload, $isPhysical): AddOn {
            $addOn = AddOn::query()->create([
                'package_id' => $payload['package_id'] ?? null,
                'code' => trim((string) ($payload['code'] ?? '')) ?: $this->nextCode(),
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'price' => $payload['price'],
                'max_qty' => $payload['max_qty'],
                'is_physical' => $isPhysical,
                'available_stock' => 0,
                'low_stock_threshold' => 0,
                'is_active' => $payload['is_active'] ?? true,
                'sort_order' => $payload['sort_order'] ?? 0,
            ]);

            if (array_key_exists('inventory_items', $payload)) {
                $this->inventoryService->syncAddOnConsumptions($addOn, $payload['inventory_items'] ?? []);
            }

            return $addOn->refresh();
        });
    }

    public function update(AddOn $addOn, array $payload): AddOn
    {
        $codeInput = trim((string) ($payload['code'] ?? ''));
        $isPhysical = array_key_exists('is_physical', $payload)
            ? (bool) $payload['is_physical']
            : (bool) $addOn->is_physical;

        return DB::transaction(function () use ($addOn, $payload, $codeInput, $isPhysical): AddOn {
            $addOn->update([
                'package_id' => array_key_exists('package_id', $payload) ? $payload['package_id'] : $addOn->package_id,
                'code' => $codeInput !== '' ? $codeInput : $addOn->code,
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'price' => $payload['price'],
                'max_qty' => $payload['max_qty'],
                'is_physical' => $isPhysical,
                'is_active' => $payload['is_active'] ?? $addOn->is_active,
                'sort_order' => $payload['sort_order'] ?? $addOn->sort_order,
            ]);

            if (array_key_exists('inventory_items', $payload)) {
                $this->inventoryService->syncAddOnConsumptions($addOn, $payload['inventory_items'] ?? []);
            }

            return $addOn->refresh();
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
