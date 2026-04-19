<?php

namespace App\Services;

use App\Models\QueueTicket;
use Illuminate\Pagination\LengthAwarePaginator;

class QueueReadService
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        $query = QueueTicket::query()
            ->with(['booking'])
            ->orderByDesc('queue_date')
            ->orderBy('queue_number');

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (! empty($filters['queue_date'])) {
            $query->whereDate('queue_date', (string) $filters['queue_date']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
