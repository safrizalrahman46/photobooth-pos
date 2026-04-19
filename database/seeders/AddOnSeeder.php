<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\Package;
use Illuminate\Database\Seeder;

class AddOnSeeder extends Seeder
{
    public function run(): void
    {
        $packages = Package::query()->get(['id', 'code']);

        $addOnCatalog = [
            ['id' => 'extra-person', 'label' => '+ 1 orang (include cetak 1 4R)', 'price' => 15000],
            ['id' => 'extra-print', 'label' => '+ 1 cetak 4R', 'price' => 15000],
            ['id' => 'extra-time', 'label' => '+ 5 menit durasi foto', 'price' => 20000],
            ['id' => 'costume', 'label' => 'Sewa 1 kostum', 'price' => 10000],
            ['id' => 'ganci-bening', 'label' => 'Ganci bening 1 pcs', 'price' => 10000],
            ['id' => 'ganci-besi', 'label' => 'Ganci besi 1 pcs', 'price' => 20000],
            ['id' => 'diy', 'label' => 'DIY 1 pcs', 'price' => 5000],
        ];

        $addOnMax = [
            'extra-person' => 10,
            'extra-print' => 10,
            'extra-time' => 3,
            'costume' => 5,
            'ganci-bening' => 10,
            'ganci-besi' => 10,
            'diy' => 10,
        ];

        /**
         * =========================
         * 1. GLOBAL ADDONS
         * =========================
         */
        foreach ($addOnCatalog as $index => $item) {
            AddOn::query()->updateOrCreate(
                [
                    'code' => $item['id'],
                    'package_id' => null,
                ],
                [
                    'name' => $item['label'],
                    'description' => 'Global add-on',
                    'price' => $item['price'],
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                    'max_qty' => $addOnMax[$item['id']] ?? 1,
                ]
            );
        }
    }
}