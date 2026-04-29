<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Transaction;
use Carbon\Carbon;

class CodeGenerator
{
    public function generateBookingCode(Carbon $date): string
    {
        return $this->generateDailyCode('BKG', $date, Booking::query(), 'booking_code');
    }

    public function generateTransactionCode(Carbon $date): string
    {
        return $this->generateDailyCode('TRX', $date, Transaction::query(), 'transaction_code');
    }

    public function generatePaymentCode(Carbon $date): string
    {
        return $this->generateDailyCode('PAY', $date, Payment::query(), 'payment_code');
    }

    public function generateQueueCode(Carbon $date, int $queueNumber): string
    {
        return sprintf('Q-%s-%03d', $date->format('Ymd'), $queueNumber);
    }

    private function generateDailyCode(string $prefix, Carbon $date, $query, string $column): string
    {
        $datePart = $date->format('Ymd');
        $base = $prefix.'-'.$datePart;
        $latestCode = $query->where($column, 'like', $base.'-%')
            ->orderByDesc('id')
            ->value($column);

        $next = 1;

        if (is_string($latestCode)) {
            $parts = explode('-', $latestCode);
            $last = (int) end($parts);
            $next = $last + 1;
        }

        return sprintf('%s-%04d', $base, $next);
    }
}
