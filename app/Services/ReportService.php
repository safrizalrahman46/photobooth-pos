<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Booking;
use App\Models\QueueTicket;
use App\Models\Transaction;
use Carbon\Carbon;

class ReportService
{
    public function salesSummary(string $from, string $to, ?int $branchId = null): array
    {
        $baseQuery = Transaction::query()
            ->whereBetween('created_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ]);

        $bookingQuery = Booking::query()
            ->whereBetween('paid_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ])
            ->where('payment_type', 'full')
            ->where('status', 'paid');

        if ($branchId) {
            $baseQuery->where('branch_id', $branchId);
            $bookingQuery->where('branch_id', $branchId);
        }

        $grossSales = (float) (clone $baseQuery)->sum('total_amount');
        $paidSales = (float) (clone $baseQuery)->where('status', TransactionStatus::Paid)->sum('paid_amount');
        $transactionCount = (int) (clone $baseQuery)->count();
        $onlineBookingSales = (float) (clone $bookingQuery)->sum('paid_amount');

        return [
            'gross_sales' => $grossSales,
            'paid_sales' => $paidSales,
            'online_booking_sales' => $onlineBookingSales,
            'combined_paid_sales' => $paidSales + $onlineBookingSales,
            'transaction_count' => $transactionCount,
            'average_transaction' => $transactionCount > 0 ? round($grossSales / $transactionCount, 2) : 0,
        ];
    }

    public function bookingVolume(string $from, string $to, ?int $branchId = null): array
    {
        $query = Booking::query()->whereBetween('booking_date', [$from, $to]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total' => (int) (clone $query)->count(),
            'done' => (int) (clone $query)->where('status', 'done')->count(),
            'cancelled' => (int) (clone $query)->where('status', 'cancelled')->count(),
        ];
    }

    public function queueVolume(string $from, string $to, ?int $branchId = null): array
    {
        $query = QueueTicket::query()->whereBetween('queue_date', [$from, $to]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total' => (int) (clone $query)->count(),
            'finished' => (int) (clone $query)->where('status', 'finished')->count(),
            'waiting' => (int) (clone $query)->where('status', 'waiting')->count(),
        ];
    }
}
