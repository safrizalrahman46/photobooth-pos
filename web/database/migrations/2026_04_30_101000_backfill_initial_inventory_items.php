<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $items = [
            'INV-PAPER-4R' => ['name' => 'Kertas Foto 4R', 'unit' => 'lembar', 'available_stock' => 100, 'low_stock_threshold' => 20, 'sort_order' => 10],
            'INV-KEYCHAIN-ACRYLIC-BLANK' => ['name' => 'Blank Gantungan Kunci Akrilik', 'unit' => 'pcs', 'available_stock' => 30, 'low_stock_threshold' => 8, 'sort_order' => 20],
            'INV-KEYCHAIN-METAL-BLANK' => ['name' => 'Blank Gantungan Kunci Metal', 'unit' => 'pcs', 'available_stock' => 20, 'low_stock_threshold' => 5, 'sort_order' => 30],
            'INV-DIY-CARD' => ['name' => 'DIY Card', 'unit' => 'pcs', 'available_stock' => 40, 'low_stock_threshold' => 10, 'sort_order' => 40],
        ];

        foreach ($items as $code => $row) {
            DB::table('inventory_items')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $row['name'],
                    'unit' => $row['unit'],
                    'available_stock' => $row['available_stock'],
                    'low_stock_threshold' => $row['low_stock_threshold'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $itemMap = DB::table('inventory_items')->pluck('id', 'code');
        $paperId = $itemMap['INV-PAPER-4R'] ?? null;

        if ($paperId) {
            foreach (DB::table('packages')->pluck('id') as $packageId) {
                DB::table('package_inventory_items')->updateOrInsert(
                    ['package_id' => $packageId, 'inventory_item_id' => $paperId],
                    ['qty_per_booking' => 1, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        $addOnMappings = [
            'AON-EXTRA-PERSON' => ['INV-PAPER-4R' => 1],
            'AON-EXTRA-PRINT' => ['INV-PAPER-4R' => 1],
            'AON-KEYCHAIN-ACRYLIC' => ['INV-KEYCHAIN-ACRYLIC-BLANK' => 1],
            'AON-KEYCHAIN-METAL' => ['INV-KEYCHAIN-METAL-BLANK' => 1],
            'AON-DIY-CARD' => ['INV-DIY-CARD' => 1],
        ];

        foreach ($addOnMappings as $addOnCode => $rows) {
            $addOnId = DB::table('add_ons')->where('code', $addOnCode)->value('id');

            if (! $addOnId) {
                continue;
            }

            foreach ($rows as $itemCode => $qty) {
                $itemId = $itemMap[$itemCode] ?? null;

                if (! $itemId) {
                    continue;
                }

                DB::table('add_on_inventory_items')->updateOrInsert(
                    ['add_on_id' => $addOnId, 'inventory_item_id' => $itemId],
                    ['qty_per_unit' => $qty, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('add_on_inventory_items')->whereIn('inventory_item_id', function ($query): void {
            $query->select('id')->from('inventory_items')->whereIn('code', [
                'INV-PAPER-4R',
                'INV-KEYCHAIN-ACRYLIC-BLANK',
                'INV-KEYCHAIN-METAL-BLANK',
                'INV-DIY-CARD',
            ]);
        })->delete();

        DB::table('package_inventory_items')->whereIn('inventory_item_id', function ($query): void {
            $query->select('id')->from('inventory_items')->whereIn('code', [
                'INV-PAPER-4R',
                'INV-KEYCHAIN-ACRYLIC-BLANK',
                'INV-KEYCHAIN-METAL-BLANK',
                'INV-DIY-CARD',
            ]);
        })->delete();

        DB::table('inventory_items')->whereIn('code', [
            'INV-PAPER-4R',
            'INV-KEYCHAIN-ACRYLIC-BLANK',
            'INV-KEYCHAIN-METAL-BLANK',
            'INV-DIY-CARD',
        ])->delete();
    }
};
