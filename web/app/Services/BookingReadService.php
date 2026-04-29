<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Pagination\LengthAwarePaginator;

class BookingReadService
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        $query = Booking::query()
            ->with(['package', 'designCatalog'])
            ->orderByDesc('booking_date')
            ->orderByDesc('start_at');

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('booking_date', (string) $filters['date']);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
