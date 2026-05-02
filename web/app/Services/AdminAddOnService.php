<?php

namespace App\Services;

use App\Models\AddOn;
use Illuminate\Support\Facades\DB;

class AdminAddOnService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly ActivityLogger $activityLogger,
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

            $addOn = $addOn->refresh();

            $this->activityLogger->log(
                'add-ons',
                'created',
                null,
                AddOn::class,
                (int) $addOn->id,
                [
                    'message' => sprintf('Add-on %s dibuat.', (string) $addOn->name),
                    'label' => (string) $addOn->code,
                    'name' => (string) $addOn->name,
                    'package_id' => $addOn->package_id ? (int) $addOn->package_id : null,
                    'price' => (float) $addOn->price,
                    'max_qty' => (int) $addOn->max_qty,
                ],
            );

            return $addOn;
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

            $addOn = $addOn->refresh();

            $this->activityLogger->log(
                'add-ons',
                'updated',
                null,
                AddOn::class,
                (int) $addOn->id,
                [
                    'message' => sprintf('Add-on %s diperbarui.', (string) $addOn->name),
                    'label' => (string) $addOn->code,
                    'name' => (string) $addOn->name,
                    'package_id' => $addOn->package_id ? (int) $addOn->package_id : null,
                    'price' => (float) $addOn->price,
                    'max_qty' => (int) $addOn->max_qty,
                    'updated_fields' => array_keys($payload),
                ],
            );

            return $addOn;
        });
    }

    public function delete(AddOn $addOn): void
    {
        $this->activityLogger->log(
            'add-ons',
            'deleted',
            null,
            AddOn::class,
            (int) $addOn->id,
            [
                'message' => sprintf('Add-on %s dihapus.', (string) $addOn->name),
                'label' => (string) $addOn->code,
                'name' => (string) $addOn->name,
            ],
        );

        $addOn->delete();
    }

    public function managementRows(): array
    {
        return AddOn::query()
            ->with(['package:id,name', 'inventoryItems:id,code,name,unit,available_stock,low_stock_threshold,is_active'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'package_id',
                'code',
                'name',
                'description',
                'price',
                'max_qty',
                'is_physical',
                'is_active',
                'sort_order',
                'created_at',
                'updated_at',
            ])
            ->map(function (AddOn $addOn): array {
                $price = (float) $addOn->price;
                $inventoryItems = $this->inventoryService->mapAddOnInventoryItems($addOn);
                $effectiveStock = $this->inventoryService->effectiveAvailableStock($inventoryItems);
                $stockTone = $this->inventoryService->effectiveStockTone($inventoryItems, $effectiveStock);

                return [
                    'id' => (int) $addOn->id,
                    'package_id' => $addOn->package_id ? (int) $addOn->package_id : null,
                    'package_name' => (string) ($addOn->package?->name ?? 'Global'),
                    'code' => (string) $addOn->code,
                    'name' => (string) $addOn->name,
                    'description' => (string) ($addOn->description ?? ''),
                    'price' => $price,
                    'price_text' => $this->formatRupiah($price),
                    'max_qty' => max(1, (int) $addOn->max_qty),
                    'is_physical' => (bool) $addOn->is_physical,
                    'inventory_items' => $inventoryItems,
                    'effective_available_stock' => $effectiveStock,
                    'effective_stock_status' => $stockTone['status'],
                    'effective_stock_label' => $stockTone['label'],
                    'type_label' => $addOn->is_physical ? 'Physical' : 'Non-physical',
                    'is_active' => (bool) $addOn->is_active,
                    'sort_order' => (int) $addOn->sort_order,
                    'created_at' => $addOn->created_at?->toIso8601String(),
                    'updated_at' => $addOn->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
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

    private function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
