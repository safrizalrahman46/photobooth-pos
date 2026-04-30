<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\InventoryItem;
use App\Models\Package;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'code' => 'INV-PAPER-4R',
                'name' => 'Kertas Foto 4R',
                'unit' => 'lembar',
                'available_stock' => 100,
                'low_stock_threshold' => 20,
                'sort_order' => 10,
            ],
            [
                'code' => 'INV-KEYCHAIN-ACRYLIC-BLANK',
                'name' => 'Blank Gantungan Kunci Akrilik',
                'unit' => 'pcs',
                'available_stock' => 30,
                'low_stock_threshold' => 8,
                'sort_order' => 20,
            ],
            [
                'code' => 'INV-KEYCHAIN-METAL-BLANK',
                'name' => 'Blank Gantungan Kunci Metal',
                'unit' => 'pcs',
                'available_stock' => 20,
                'low_stock_threshold' => 5,
                'sort_order' => 30,
            ],
            [
                'code' => 'INV-DIY-CARD',
                'name' => 'DIY Card',
                'unit' => 'pcs',
                'available_stock' => 40,
                'low_stock_threshold' => 10,
                'sort_order' => 40,
            ],
        ];

        foreach ($items as $row) {
            InventoryItem::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'unit' => $row['unit'],
                    'available_stock' => max(0, (int) $row['available_stock']),
                    'low_stock_threshold' => max(0, (int) $row['low_stock_threshold']),
                    'is_active' => true,
                    'sort_order' => (int) $row['sort_order'],
                ]
            );
        }

        $itemMap = InventoryItem::query()->pluck('id', 'code');

        $this->syncPackageConsumption($itemMap);
        $this->syncAddOnConsumption($itemMap);
    }

    private function syncPackageConsumption($itemMap): void
    {
        $paperId = $itemMap['INV-PAPER-4R'] ?? null;

        if (! $paperId) {
            return;
        }

        Package::query()->each(function (Package $package) use ($paperId): void {
            $this->upsertPackagePivot($package, (int) $paperId, 1);
        });
    }

    private function syncAddOnConsumption($itemMap): void
    {
        $mapping = [
            'AON-EXTRA-PERSON' => ['INV-PAPER-4R' => 1],
            'AON-EXTRA-PRINT' => ['INV-PAPER-4R' => 1],
            'AON-KEYCHAIN-ACRYLIC' => ['INV-KEYCHAIN-ACRYLIC-BLANK' => 1],
            'AON-KEYCHAIN-METAL' => ['INV-KEYCHAIN-METAL-BLANK' => 1],
            'AON-DIY-CARD' => ['INV-DIY-CARD' => 1],
        ];

        foreach ($mapping as $addOnCode => $rows) {
            $addOn = AddOn::query()->where('code', $addOnCode)->first();

            if (! $addOn) {
                continue;
            }

            foreach ($rows as $itemCode => $qty) {
                $itemId = $itemMap[$itemCode] ?? null;

                if (! $itemId) {
                    continue;
                }

                $this->upsertAddOnPivot($addOn, (int) $itemId, (int) $qty);
            }
        }
    }

    private function upsertPackagePivot(Package $package, int $inventoryItemId, int $qty): void
    {
        if ($package->inventoryItems()->whereKey($inventoryItemId)->exists()) {
            $package->inventoryItems()->updateExistingPivot($inventoryItemId, [
                'qty_per_booking' => max(1, $qty),
            ]);

            return;
        }

        $package->inventoryItems()->attach($inventoryItemId, [
            'qty_per_booking' => max(1, $qty),
        ]);
    }

    private function upsertAddOnPivot(AddOn $addOn, int $inventoryItemId, int $qty): void
    {
        if ($addOn->inventoryItems()->whereKey($inventoryItemId)->exists()) {
            $addOn->inventoryItems()->updateExistingPivot($inventoryItemId, [
                'qty_per_unit' => max(1, $qty),
            ]);

            return;
        }

        $addOn->inventoryItems()->attach($inventoryItemId, [
            'qty_per_unit' => max(1, $qty),
        ]);
    }
}
