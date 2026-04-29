<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\TimeSlot;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::query()->where('is_active', true)->get(['id']);

        if ($branches->isEmpty()) {
            return;
        }

        $ranges = [
            ['10:00:00', '11:00:00'],
            ['11:00:00', '12:00:00'],
            ['13:00:00', '14:00:00'],
            ['14:00:00', '15:00:00'],
            ['15:00:00', '16:00:00'],
            ['17:00:00', '18:00:00'],
            ['18:00:00', '19:00:00'],
            ['19:00:00', '20:00:00'],
            ['20:00:00', '21:00:00'],
        ];

        foreach ($branches as $branch) {
            $period = CarbonPeriod::create(now()->startOfDay(), '1 day', now()->startOfDay()->addDays(21));

            foreach ($period as $date) {
                foreach ($ranges as [$start, $end]) {
                    TimeSlot::query()->updateOrCreate(
                        [
                            'branch_id' => $branch->id,
                            'slot_date' => $date->format('Y-m-d'),
                            'start_time' => $start,
                            'end_time' => $end,
                        ],
                        [
                            'capacity' => 2,
                            'is_bookable' => true,
                        ]
                    );
                }
            }
        }
    }
}
