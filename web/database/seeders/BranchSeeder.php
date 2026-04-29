<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'code' => 'JKT-CT',
                'name' => 'Jakarta Central',
                'timezone' => 'Asia/Jakarta',
                'phone' => '021-555-9001',
                'address' => 'Jl. Sudirman No. 21, Jakarta Pusat',
                'is_active' => true,
            ],
            [
                'code' => 'BDG-RG',
                'name' => 'Bandung Riau',
                'timezone' => 'Asia/Jakarta',
                'phone' => '022-555-8802',
                'address' => 'Jl. Riau No. 88, Bandung',
                'is_active' => true,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::query()->updateOrCreate(
                ['code' => $branch['code']],
                $branch
            );
        }
    }
}
