<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\Package;
use Illuminate\Database\Seeder;

class AddOnSeeder extends Seeder
{
    public function run(): void
    {
        $packageMap = Package::query()->pluck('id', 'code');

        $globalAddOns = [
            [
                'code' => 'AON-EXTRA-PERSON',
                'name' => '+ 1 orang (include cetak 1 lembar 4R)',
                'description' => 'Tambah 1 orang untuk satu sesi foto.',
                'price' => 15000,
                'max_qty' => 10,
                'is_physical' => false,
                'available_stock' => 0,
                'low_stock_threshold' => 0,
                'sort_order' => 10,
            ],
            [
                'code' => 'AON-EXTRA-PRINT',
                'name' => '+ 1 cetak 4R',
                'description' => 'Tambahan cetak ukuran 4R.',
                'price' => 15000,
                'max_qty' => 10,
                'is_physical' => false,
                'available_stock' => 0,
                'low_stock_threshold' => 0,
                'sort_order' => 20,
            ],
            [
                'code' => 'AON-EXTRA-TIME',
                'name' => '+ 5 menit durasi sesi',
                'description' => 'Perpanjang waktu sesi sebanyak 5 menit.',
                'price' => 20000,
                'max_qty' => 3,
                'is_physical' => false,
                'available_stock' => 0,
                'low_stock_threshold' => 0,
                'sort_order' => 30,
            ],
            [
                'code' => 'AON-COSTUME',
                'name' => 'Sewa 1 kostum',
                'description' => 'Sewa kostum untuk sesi berjalan.',
                'price' => 10000,
                'max_qty' => 5,
                'is_physical' => true,
                'available_stock' => 25,
                'low_stock_threshold' => 5,
                'sort_order' => 40,
            ],
            [
                'code' => 'AON-KEYCHAIN-ACRYLIC',
                'name' => 'Gantungan kunci akrilik 1 pcs',
                'description' => 'Cetak foto pada gantungan kunci akrilik.',
                'price' => 10000,
                'max_qty' => 10,
                'is_physical' => true,
                'available_stock' => 30,
                'low_stock_threshold' => 8,
                'sort_order' => 50,
            ],
            [
                'code' => 'AON-KEYCHAIN-METAL',
                'name' => 'Gantungan kunci metal 1 pcs',
                'description' => 'Cetak foto pada gantungan kunci metal.',
                'price' => 20000,
                'max_qty' => 10,
                'is_physical' => true,
                'available_stock' => 20,
                'low_stock_threshold' => 5,
                'sort_order' => 60,
            ],
            [
                'code' => 'AON-DIY-CARD',
                'name' => 'DIY card 1 pcs',
                'description' => 'Aksesori DIY card untuk kenang-kenangan.',
                'price' => 5000,
                'max_qty' => 10,
                'is_physical' => true,
                'available_stock' => 40,
                'low_stock_threshold' => 10,
                'sort_order' => 70,
            ],
        ];

        foreach ($globalAddOns as $row) {
            AddOn::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'package_id' => null,
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'max_qty' => $row['max_qty'],
                    'is_physical' => (bool) ($row['is_physical'] ?? false),
                    'available_stock' => max(0, (int) ($row['available_stock'] ?? 0)),
                    'low_stock_threshold' => max(0, (int) ($row['low_stock_threshold'] ?? 0)),
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                ]
            );
        }

        $packageSpecificAddOns = [
            'PKG-EXPRESS' => [
                [
                    'code' => 'AON-EXPRESS-PRIORITY-PRINT',
                    'name' => 'Priority print',
                    'description' => 'Prioritas cetak hasil sesi untuk paket Express Strip.',
                    'price' => 12000,
                    'max_qty' => 1,
                    'is_physical' => false,
                    'available_stock' => 0,
                    'low_stock_threshold' => 0,
                    'sort_order' => 110,
                ],
            ],
            'PKG-PARTY' => [
                [
                    'code' => 'AON-PARTY-PROP-SET',
                    'name' => 'Party props set',
                    'description' => 'Set properti party tambahan untuk sesi grup.',
                    'price' => 25000,
                    'max_qty' => 2,
                    'is_physical' => true,
                    'available_stock' => 15,
                    'low_stock_threshold' => 4,
                    'sort_order' => 120,
                ],
            ],
            'PKG-PREMIUM' => [
                [
                    'code' => 'AON-PREMIUM-EXTENDED-LIGHTING',
                    'name' => 'Extended lighting setup',
                    'description' => 'Pengaturan lighting lanjutan untuk paket Premium Event.',
                    'price' => 35000,
                    'max_qty' => 1,
                    'is_physical' => true,
                    'available_stock' => 8,
                    'low_stock_threshold' => 2,
                    'sort_order' => 130,
                ],
            ],
        ];

        foreach ($packageSpecificAddOns as $packageCode => $rows) {
            $packageId = $packageMap[$packageCode] ?? null;

            if (! $packageId) {
                continue;
            }

            foreach ($rows as $row) {
                AddOn::query()->updateOrCreate(
                    ['code' => $row['code']],
                    [
                        'package_id' => $packageId,
                        'name' => $row['name'],
                        'description' => $row['description'],
                        'price' => $row['price'],
                        'max_qty' => $row['max_qty'],
                        'is_physical' => (bool) ($row['is_physical'] ?? false),
                        'available_stock' => max(0, (int) ($row['available_stock'] ?? 0)),
                        'low_stock_threshold' => max(0, (int) ($row['low_stock_threshold'] ?? 0)),
                        'is_active' => true,
                        'sort_order' => $row['sort_order'],
                    ]
                );
            }
        }
    }
}
