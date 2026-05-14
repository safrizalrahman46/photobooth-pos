<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionReadService
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        $query = Transaction::query()
            ->with(['items', 'payments'])
            ->orderByDesc('created_at');

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function recentDetailed(int $limit = 25): array
    {
        return Transaction::query()
            ->with([
                'branch:id,name',
                'cashier:id,name',
                'booking:id,customer_name,customer_phone',
                'queueTicket:id,customer_name,customer_phone',
                'items:id,transaction_id,item_type,item_ref_id,item_name,qty,unit_price,line_total',
                'payments:id,transaction_id,payment_code,method,amount,reference_no,paid_at,cashier_id',
                'payments.cashier:id,name',
            ])
            ->latest('created_at')
            ->limit(max(1, min($limit, 100)))
            ->get([
                'id',
                'transaction_code',
                'branch_id',
                'cashier_id',
                'booking_id',
                'queue_ticket_id',
                'subtotal',
                'discount_amount',
                'referral_code_id',
                'referral_code',
                'referral_discount_amount',
                'total_amount',
                'paid_amount',
                'change_amount',
                'status',
                'notes',
                'paid_at',
                'created_at',
            ])
            ->map(fn (Transaction $transaction): array => $this->mapRecentTransaction($transaction))
            ->values()
            ->all();
    }

    protected function mapRecentTransaction(Transaction $transaction): array
    {
        $latestPaymentMethod = $transaction->payments
            ->sortByDesc('id')
            ->first()?->method?->value;
        $customerName = (string) ($transaction->booking?->customer_name ?? $transaction->queueTicket?->customer_name ?? '-');
        $customerPhone = (string) ($transaction->booking?->customer_phone ?? $transaction->queueTicket?->customer_phone ?? '');
        $totalAmount = (float) $transaction->total_amount;
        $paidAmount = (float) $transaction->paid_amount;
        $remainingAmount = max($totalAmount - $paidAmount, 0);

        return [
            'record_id' => (int) $transaction->id,
            'code' => (string) $transaction->transaction_code,
            'branch_name' => (string) ($transaction->branch?->name ?? '-'),
            'customer' => $customerName,
            'customer_phone' => $customerPhone,
            'cashier' => (string) ($transaction->cashier?->name ?? '-'),
            'method' => $latestPaymentMethod ? strtoupper($latestPaymentMethod) : '-',
            'amount' => (float) ($paidAmount > 0 ? $paidAmount : $totalAmount),
            'subtotal_amount' => (float) $transaction->subtotal,
            'discount_amount' => (float) $transaction->discount_amount,
            'discount_text' => $this->formatRupiah((float) $transaction->discount_amount),
            'referral_code_id' => $transaction->referral_code_id ? (int) $transaction->referral_code_id : null,
            'referral_code' => (string) ($transaction->referral_code ?? ''),
            'referral_discount_amount' => (float) $transaction->referral_discount_amount,
            'total_amount' => $totalAmount,
            'total_text' => $this->formatRupiah($totalAmount),
            'paid_amount' => $paidAmount,
            'paid_text' => $this->formatRupiah($paidAmount),
            'remaining_amount' => $remainingAmount,
            'remaining_text' => $this->formatRupiah($remainingAmount),
            'change_amount' => (float) $transaction->change_amount,
            'change_text' => $this->formatRupiah((float) $transaction->change_amount),
            'status' => (string) $transaction->status->value,
            'notes' => (string) ($transaction->notes ?? ''),
            'items' => $transaction->items
                ->map(function ($item): array {
                    $unitPrice = (float) $item->unit_price;
                    $lineTotal = (float) $item->line_total;

                    return [
                        'item_type' => (string) $item->item_type,
                        'item_name' => (string) $item->item_name,
                        'qty' => (int) $item->qty,
                        'unit_price' => $unitPrice,
                        'unit_price_text' => $this->formatRupiah($unitPrice),
                        'line_total' => $lineTotal,
                        'line_total_text' => $this->formatRupiah($lineTotal),
                    ];
                })
                ->values()
                ->all(),
            'payments' => $transaction->payments
                ->sortBy('paid_at')
                ->values()
                ->map(function (Payment $payment): array {
                    $amount = (float) $payment->amount;

                    return [
                        'payment_code' => (string) $payment->payment_code,
                        'method' => strtoupper((string) ($payment->method?->value ?? $payment->method)),
                        'amount' => $amount,
                        'amount_text' => $this->formatRupiah($amount),
                        'reference_no' => (string) ($payment->reference_no ?? ''),
                        'cashier_name' => (string) ($payment->cashier?->name ?? '-'),
                        'paid_at' => $payment->paid_at?->toIso8601String(),
                        'paid_at_text' => $payment->paid_at?->translatedFormat('d M Y, H:i') ?? '-',
                    ];
                })
                ->all(),
            'time' => $transaction->created_at?->diffForHumans() ?? '-',
            'time_text' => $transaction->created_at?->translatedFormat('d M Y, H:i') ?? '-',
            'created_at' => $transaction->created_at?->toIso8601String(),
            'paid_at' => $transaction->paid_at?->toIso8601String(),
        ];
    }

    protected function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
