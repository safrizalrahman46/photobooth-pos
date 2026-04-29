<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DesignCatalog;
use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $branchMap = Branch::query()->pluck('id', 'code');

        $packages = [
            [
                'branch_code' => null,
                'code' => 'PKG-EXPRESS',
                'name' => 'Express Strip',
                'description' => 'Sesi cepat untuk 2-4 orang dengan hasil strip klasik.',
                'duration_minutes' => 15,
                'base_price' => 69000,
                'sort_order' => 10,
            ],
            [
                'branch_code' => null,
                'code' => 'PKG-PARTY',
                'name' => 'Party Session',
                'description' => 'Sesi grup dengan pilihan frame party dan tambahan properti.',
                'duration_minutes' => 30,
                'base_price' => 149000,
                'sort_order' => 20,
            ],
            [
                'branch_code' => 'JKT-CT',
                'code' => 'PKG-PREMIUM',
                'name' => 'Premium Event',
                'description' => 'Durasi lebih panjang untuk kebutuhan event dan konten.',
                'duration_minutes' => 45,
                'base_price' => 249000,
                'sort_order' => 30,
            ],
        ];

        foreach ($packages as $item) {
            $branchCode = $item['branch_code'];
            $branchId = $branchCode ? ($branchMap[$branchCode] ?? null) : null;

            $package = Package::query()->updateOrCreate(
                ['code' => $item['code']],
                [
                    'branch_id' => $branchId,
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'duration_minutes' => $item['duration_minutes'],
                    'base_price' => $item['base_price'],
                    'is_active' => true,
                    'sort_order' => $item['sort_order'],
                ]
            );

            $designs = [
                ['suffix' => 'A', 'name' => 'Classic Clean', 'theme' => 'minimal'],
                ['suffix' => 'B', 'name' => 'Fun Color Pop', 'theme' => 'party'],
            ];

            foreach ($designs as $design) {
                DesignCatalog::query()->updateOrCreate(
                    ['code' => $item['code'].'-'.$design['suffix']],
                    [
                        'package_id' => $package->id,
                        'name' => $design['name'],
                        'theme' => $design['theme'],
                        'preview_url' => null,
                        'is_active' => true,
                        'sort_order' => $design['suffix'] === 'A' ? 10 : 20,
                    ]
                );
            }
        }
    }
}
