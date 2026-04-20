<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\Booking;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class SalesTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tren Omzet Harian';

    public ?string $filter = '7';

    protected function getData(): array
    {
        $days = (int) ($this->filter ?: 7);
        $days = in_array($days, [7, 14, 30], true) ? $days : 7;

        $from = Carbon::today()->subDays($days - 1);
        $to = Carbon::today();

        $labels = [];
        $totalsByDay = [];

        for ($cursor = $from->copy(); $cursor->lte($to); $cursor->addDay()) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d M');
            $totalsByDay[$key] = 0.0;
        }

        $posData = Transaction::query()
            ->selectRaw('DATE(paid_at) as day, SUM(paid_amount) as total')
            ->whereBetween('paid_at', [$from->startOfDay(), $to->copy()->endOfDay()])
            ->where('status', TransactionStatus::Paid)
            ->groupBy('day')
            ->pluck('total', 'day')
            ->all();

        $bookingData = Booking::query()
            ->selectRaw('DATE(paid_at) as day, SUM(paid_amount) as total')
            ->whereBetween('paid_at', [$from->startOfDay(), $to->copy()->endOfDay()])
            ->where('status', 'paid')
            ->where('payment_type', 'full')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->all();

        foreach ($posData as $day => $total) {
            $key = Carbon::parse($day)->toDateString();

            if (array_key_exists($key, $totalsByDay)) {
                $totalsByDay[$key] += (float) $total;
            }
        }

        foreach ($bookingData as $day => $total) {
            $key = Carbon::parse($day)->toDateString();

            if (array_key_exists($key, $totalsByDay)) {
                $totalsByDay[$key] += (float) $total;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Omzet',
                    'data' => array_values(array_map(fn ($value) => round($value, 2), $totalsByDay)),
                    'borderColor' => '#0f766e',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 hari',
            '14' => '14 hari',
            '30' => '30 hari',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
