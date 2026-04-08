<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Booking;
use App\Models\QueueTicket;
use App\Models\Transaction;
use Carbon\Carbon;

class ReportService
{
    public function salesSummary(string $from, string $to): array
    {
        $baseQuery = Transaction::query()
            ->whereBetween('created_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ]);

        $grossSales = (float) (clone $baseQuery)->sum('total_amount');
        $paidSales = (float) (clone $baseQuery)->where('status', TransactionStatus::Paid)->sum('paid_amount');
        $transactionCount = (int) (clone $baseQuery)->count();

        return [
            'gross_sales' => $grossSales,
            'paid_sales' => $paidSales,
            'transaction_count' => $transactionCount,
            'average_transaction' => $transactionCount > 0 ? round($grossSales / $transactionCount, 2) : 0,
        ];
    }

    public function bookingVolume(string $from, string $to): array
    {
        $query = Booking::query()->whereBetween('booking_date', [$from, $to]);

        return [
            'total' => (int) (clone $query)->count(),
            'done' => (int) (clone $query)->where('status', 'done')->count(),
            'cancelled' => (int) (clone $query)->where('status', 'cancelled')->count(),
        ];
    }

    public function queueVolume(string $from, string $to): array
    {
        $query = QueueTicket::query()->whereBetween('queue_date', [$from, $to]);

        return [
            'total' => (int) (clone $query)->count(),
            'finished' => (int) (clone $query)->where('status', 'finished')->count(),
            'waiting' => (int) (clone $query)->where('status', 'waiting')->count(),
        ];
    }
}
