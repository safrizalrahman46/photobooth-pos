<?php

namespace App\Filament\Widgets;

use App\Enums\QueueStatus;
use App\Enums\TransactionStatus;
use App\Models\Booking;
use App\Models\QueueTicket;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget;

class BusinessOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Ringkasan Operasional Hari Ini';

    protected function getStats(): array
    {
        $today = Carbon::today();

        $posPaid = (float) Transaction::query()
            ->whereDate('paid_at', $today)
            ->where('status', TransactionStatus::Paid)
            ->sum('paid_amount');

        $onlinePaid = (float) Booking::query()
            ->whereDate('paid_at', $today)
            ->where('payment_type', 'full')
            ->where('status', 'paid')
            ->sum('paid_amount');

        $todayRevenue = $posPaid + $onlinePaid;

        $todayBookings = (int) Booking::query()
            ->whereDate('booking_date', $today)
            ->count();

        $waitingQueue = (int) QueueTicket::query()
            ->whereDate('queue_date', $today)
            ->where('status', QueueStatus::Waiting)
            ->count();

        $activeQueue = (int) QueueTicket::query()
            ->whereDate('queue_date', $today)
            ->whereIn('status', [
                QueueStatus::Called,
                QueueStatus::CheckedIn,
                QueueStatus::InSession,
            ])
            ->count();

        return [
            Stat::make('Omzet Hari Ini', $this->formatRupiah($todayRevenue))
                ->description('POS + pembayaran online terkonfirmasi')
                ->descriptionColor('success')
                ->color('success')
                ->chart([10, 12, 13, 12, 14, 13, 15]),
            Stat::make('Booking Hari Ini', (string) $todayBookings)
                ->description('Total booking terjadwal hari ini')
                ->descriptionColor('info')
                ->color('info')
                ->chart([3, 4, 5, 4, 6, 7, 8]),
            Stat::make('Queue Menunggu', (string) $waitingQueue)
                ->description('Antrean dengan status waiting')
                ->descriptionColor('warning')
                ->color('warning')
                ->chart([2, 3, 2, 4, 3, 3, 2]),
            Stat::make('Queue Aktif', (string) $activeQueue)
                ->description('Called, checked in, in session')
                ->descriptionColor('primary')
                ->color('primary')
                ->chart([1, 2, 1, 2, 3, 2, 3]),
        ];
    }

    private function formatRupiah(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }
}
