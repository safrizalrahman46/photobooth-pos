<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Transaction;

class AdminPaymentService
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function rows(array $filters = []): array
    {
        $query = Payment::query()
            ->with([
                'transaction:id,transaction_code,branch_id,booking_id,total_amount,paid_amount,status',
                'transaction.branch:id,name',
                'transaction.booking:id,customer_name',
                'cashier:id,name',
            ])
            ->orderByDesc('paid_at');

        if (! empty($filters['branch_id'])) {
            $branchId = (int) $filters['branch_id'];
            $query->whereHas('transaction', fn ($builder) => $builder->where('branch_id', $branchId));
        }

        if (! empty($filters['method'])) {
            $query->where('method', (string) $filters['method']);
        }

        if (! empty($filters['paid_date'])) {
            $query->whereDate('paid_at', (string) $filters['paid_date']);
        }

        if (! empty($filters['transaction_status'])) {
            $status = (string) $filters['transaction_status'];
            $query->whereHas('transaction', fn ($builder) => $builder->where('status', $status));
        }

        $limit = max(1, min((int) ($filters['limit'] ?? 150), 500));

        return $query
            ->limit($limit)
            ->get()
            ->map(fn (Payment $payment): array => $this->mapPaymentRow($payment))
            ->values()
            ->all();
    }

    public function transactionOptions(int $limit = 100): array
    {
        return Transaction::query()
            ->with(['branch:id,name', 'booking:id,customer_name'])
            ->whereIn('status', [TransactionStatus::Unpaid->value, TransactionStatus::Partial->value])
            ->orderByDesc('created_at')
            ->limit(max(1, min($limit, 300)))
            ->get(['id', 'transaction_code', 'branch_id', 'booking_id', 'total_amount', 'paid_amount', 'status'])
            ->map(function (Transaction $transaction): array {
                $total = (float) $transaction->total_amount;
                $paid = (float) $transaction->paid_amount;
                $remaining = max($total - $paid, 0);

                return [
                    'id' => (int) $transaction->id,
                    'transaction_code' => (string) $transaction->transaction_code,
                    'branch_id' => (int) $transaction->branch_id,
                    'branch_name' => (string) ($transaction->branch?->name ?? '-'),
                    'customer_name' => (string) ($transaction->booking?->customer_name ?? '-'),
                    'status' => (string) ($transaction->status?->value ?? $transaction->status),
                    'total_amount' => $total,
                    'paid_amount' => $paid,
                    'remaining_amount' => $remaining,
                ];
            })
            ->values()
            ->all();
    }

    public function storePayment(Transaction $transaction, array $payload, int $cashierId): Transaction
    {
        return $this->transactionService->addPayment($transaction, $payload, $cashierId);
    }

    private function mapPaymentRow(Payment $payment): array
    {
        return [
            'id' => (int) $payment->id,
            'payment_code' => (string) $payment->payment_code,
            'transaction_id' => (int) $payment->transaction_id,
            'transaction_code' => (string) ($payment->transaction?->transaction_code ?? '-'),
            'branch_name' => (string) ($payment->transaction?->branch?->name ?? '-'),
            'customer_name' => (string) ($payment->transaction?->booking?->customer_name ?? '-'),
            'method' => strtoupper((string) ($payment->method?->value ?? $payment->method)),
            'amount' => (float) $payment->amount,
            'amount_text' => $this->formatRupiah((float) $payment->amount),
            'reference_no' => (string) ($payment->reference_no ?? ''),
            'cashier_name' => (string) ($payment->cashier?->name ?? '-'),
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'paid_at_text' => $payment->paid_at?->format('d M Y H:i') ?? '-',
            'transaction_status' => (string) ($payment->transaction?->status?->value ?? $payment->transaction?->status ?? 'unpaid'),
            'created_at' => $payment->created_at?->toIso8601String(),
            'updated_at' => $payment->updated_at?->toIso8601String(),
        ];
    }

    private function formatRupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}

