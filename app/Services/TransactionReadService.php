<?php

namespace App\Services;

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
}
