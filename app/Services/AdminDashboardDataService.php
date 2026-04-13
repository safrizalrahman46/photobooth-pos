<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminDashboardDataService
{
    public function stats(): array
    {
        $today = now()->toDateString();

        return [
            [
                'label' => 'Total Booking',
                'value' => number_format(Booking::query()->count()),
                'icon' => 'calendar',
                'color' => '#2563EB',
            ],
            [
                'label' => 'Hari Ini',
                'value' => number_format(Booking::query()->whereDate('booking_date', $today)->count()),
                'icon' => 'camera',
                'color' => '#EC4899',
            ],
            [
                'label' => 'Pengguna Aktif',
                'value' => number_format(User::query()->where('is_active', true)->count()),
                'icon' => 'users',
                'color' => '#22C55E',
            ],
            [
                'label' => 'Pendapatan',
                'value' => $this->formatRupiah((float) Booking::query()->sum('paid_amount')),
                'icon' => 'trending',
                'color' => '#F59E0B',
            ],
        ];
    }

    public function paginatedRows(string $search = '', string $status = 'all', int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        $query = Booking::query()
            ->with([
                'package:id,name',
                'transaction.items:id,transaction_id,item_type,item_name,qty,unit_price,line_total',
            ])
            ->latest('booking_date')
            ->latest('start_at');

        $this->applyStatusFilter($query, $status);
        $this->applySearchFilter($query, $search);

        $paginator = $query->paginate($perPage)->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Booking $booking): array => $this->toDashboardRow($booking))
        );

        return $paginator;
    }

    protected function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status === 'all') {
            return;
        }

        match ($status) {
            'pending' => $query->where('status', 'pending'),
            'booked' => $query->whereIn('status', ['confirmed', 'paid', 'checked_in', 'in_queue', 'in_session']),
            'used' => $query->where('status', 'done'),
            'expired' => $query->where('status', 'cancelled'),
            default => null,
        };
    }

    protected function applySearchFilter(Builder $query, string $search): void
    {
        $search = trim($search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $nested) use ($search): void {
            $nested->where('booking_code', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhereHas('package', function (Builder $packageQuery) use ($search): void {
                    $packageQuery->where('name', 'like', "%{$search}%");
                });
        });
    }

    protected function toDashboardRow(Booking $booking): array
    {
        $status = $this->mapUiStatus((string) $booking->status->value);

        $addOns = collect($booking->transaction?->items ?? [])
            ->filter(function ($item): bool {
                return ! in_array(strtolower((string) $item->item_type), ['package', 'main', 'booking'], true);
            })
            ->map(function ($item): array {
                return [
                    'label' => (string) $item->item_name,
                    'qty' => (int) $item->qty,
                    'line_total' => (float) $item->line_total,
                ];
            })
            ->values();

        $amount = (float) $booking->total_amount;
        if ($amount <= 0) {
            $amount = (float) $booking->paid_amount;
        }

        return [
            'id' => (string) $booking->booking_code,
            'name' => (string) $booking->customer_name,
            'date' => $booking->booking_date?->format('j M Y') ?? '-',
            'time' => $booking->start_at?->format('H:i') ?? '-',
            'status' => $status,
            'payment' => $this->paymentLabel($booking),
            'pkg' => (string) ($booking->package?->name ?? '-'),
            'amount' => $amount,
            'amount_text' => $amount > 0 ? $this->formatRupiah($amount) : '-',
            'add_ons' => $addOns,
            'add_ons_count' => $addOns->count(),
            'add_ons_total' => (float) $addOns->sum('line_total'),
        ];
    }

    protected function mapUiStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'pending',
            'done' => 'used',
            'cancelled' => 'expired',
            default => 'booked',
        };
    }

    protected function paymentLabel(Booking $booking): string
    {
        $total = (float) $booking->total_amount;
        $paid = (float) $booking->paid_amount;

        if ($paid <= 0) {
            return '-';
        }

        if ($total > 0 && $paid >= $total) {
            return 'Full';
        }

        return 'DP';
    }

    protected function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
