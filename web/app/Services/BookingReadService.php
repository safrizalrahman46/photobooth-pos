<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\TransactionStatus;
use App\Models\Booking;
use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class BookingReadService
{
    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->paginatedRows(
            (string) ($filters['search'] ?? ''),
            (string) ($filters['status'] ?? 'all'),
            $perPage,
            (string) ($filters['sort_by'] ?? 'date_time'),
            (string) ($filters['sort_dir'] ?? 'desc'),
        );
    }

    public function rowsPayload(
        string $search = '',
        string $status = 'all',
        int $perPage = 15,
        string $sortBy = 'date_time',
        string $sortDir = 'desc',
    ): array {
        $paginator = $this->paginatedRows($search, $status, $perPage, $sortBy, $sortDir);

        return [
            'rows' => $paginator->items(),
            'pagination' => $this->paginationMeta($paginator),
            'initialRows' => $paginator->items(),
            'initialPagination' => $this->paginationMeta($paginator),
            'pendingBookingsCount' => $this->pendingBookingsCount(),
        ];
    }

    public function paginatedRows(
        string $search = '',
        string $status = 'all',
        int $perPage = 15,
        string $sortBy = 'date_time',
        string $sortDir = 'desc',
    ): LengthAwarePaginator {
        $perPage = max(1, min($perPage, 100));

        $query = Booking::query()
            ->with([
                'package:id,name,base_price',
                'designCatalog:id,package_id,name',
                'branch:id,name',
                'addOns:id,code,name,price',
                'transaction:id,booking_id,total_amount,paid_amount,status',
                'transaction.payments:id,transaction_id,method',
                'transaction.items:id,transaction_id,item_type,item_ref_id,item_name,qty,unit_price,line_total',
            ]);

        $this->applyStatusFilter($query, $status);
        $this->applySearchFilter($query, $search);
        $this->applySort($query, $sortBy, $sortDir);

        $paginator = $query->paginate($perPage)->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Booking $booking): array => $this->toDashboardRow($booking))
        );

        return $paginator;
    }

    public function pendingBookingsCount(): int
    {
        return Booking::query()
            ->where('status', BookingStatus::Pending->value)
            ->count();
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

    protected function applySort(Builder $query, string $sortBy, string $sortDir): void
    {
        $direction = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        match ($sortBy) {
            'booking_code' => $query->orderBy('booking_code', $direction),
            'customer' => $query->orderBy('customer_name', $direction),
            'package' => $query->orderBy(
                Package::query()
                    ->select('name')
                    ->whereColumn('packages.id', 'bookings.package_id')
                    ->limit(1),
                $direction
            ),
            'amount' => $query
                ->orderBy('total_amount', $direction)
                ->orderBy('paid_amount', $direction),
            'payment' => $query
                ->orderBy('paid_amount', $direction)
                ->orderBy('total_amount', $direction),
            'status' => $query->orderBy('status', $direction),
            default => $query
                ->orderBy('booking_date', $direction)
                ->orderBy('start_at', $direction)
                ->orderBy('id', $direction),
        };
    }

    protected function toDashboardRow(Booking $booking): array
    {
        $status = $this->mapUiStatus((string) $booking->status->value);

        $transactionAddOns = collect($booking->transaction?->items ?? [])
            ->filter(function ($item): bool {
                return strtolower((string) $item->item_type) === 'add_on';
            })
            ->map(function ($item): array {
                return [
                    'add_on_id' => $item->item_ref_id ? (int) $item->item_ref_id : null,
                    'label' => (string) $item->item_name,
                    'qty' => (int) $item->qty,
                    'line_total' => (float) $item->line_total,
                ];
            })
            ->values();

        $bookingAddOns = collect($booking->addOns ?? [])
            ->map(function ($addOn): array {
                $qty = (int) ($addOn->pivot?->qty ?? 0);
                $lineTotal = (float) ($addOn->pivot?->line_total ?? ($qty * (float) $addOn->price));

                return [
                    'add_on_id' => (int) $addOn->id,
                    'label' => (string) $addOn->name,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                ];
            })
            ->filter(fn (array $item): bool => $item['qty'] > 0)
            ->values();

        $addOns = $transactionAddOns->isNotEmpty() ? $transactionAddOns : $bookingAddOns;

        $storedTotalAmount = (float) $booking->total_amount;
        $storedPaidAmount = (float) $booking->paid_amount;
        $transactionTotalAmount = (float) ($booking->transaction?->total_amount ?? 0);
        $transactionPaidAmount = (float) ($booking->transaction?->paid_amount ?? 0);
        $derivedTotalAmount = (float) (($booking->package?->base_price ?? 0) + (float) $addOns->sum('line_total'));
        $effectiveTotalAmount = max($storedTotalAmount, $transactionTotalAmount, $derivedTotalAmount, 0);
        $paidAmount = max($storedPaidAmount, $transactionPaidAmount, 0);
        $remainingAmount = max($effectiveTotalAmount - $paidAmount, 0);
        $paymentStatus = $this->resolveBookingPaymentStatus($booking, $effectiveTotalAmount, $paidAmount);
        $paymentLabel = $this->paymentLabel($booking, $effectiveTotalAmount, $paidAmount);
        $amount = $effectiveTotalAmount > 0 ? $effectiveTotalAmount : $paidAmount;
        $transferProofPath = $this->normalizePublicDiskPath((string) ($booking->transfer_proof_path ?? ''));
        $transferProofExists = $transferProofPath !== '' && Storage::disk('public')->exists($transferProofPath);
        $transferProofUrl = $transferProofExists
            ? route('admin.bookings.transfer-proof', ['booking' => (int) $booking->id], false)
            : '';
        $transferProofUploadedAt = $booking->transfer_proof_uploaded_at;
        $statusValue = (string) $booking->status->value;
        $isClosedStatus = in_array($statusValue, [
            BookingStatus::Cancelled->value,
            BookingStatus::Done->value,
        ], true);
        $canConfirmBooking = ! $isClosedStatus
            && $booking->approved_at === null
            && (
                $effectiveTotalAmount <= 0
                || $paidAmount > 0
            );
        $canConfirmPayment = ! $isClosedStatus
            && $booking->approved_at === null
            && $effectiveTotalAmount > 0
            && $paidAmount <= 0
            && in_array($paymentStatus, [
                TransactionStatus::Unpaid->value,
                TransactionStatus::Partial->value,
            ], true);
        $canDeclineBooking = ! $isClosedStatus
            && $booking->approved_at === null
            && ! $transferProofExists;

        return [
            'record_id' => (int) $booking->id,
            'id' => (string) $booking->booking_code,
            'booking_code' => (string) $booking->booking_code,
            'branch_id' => (int) $booking->branch_id,
            'branch_name' => (string) ($booking->branch?->name ?? '-'),
            'package_id' => (int) $booking->package_id,
            'design_catalog_id' => $booking->design_catalog_id ? (int) $booking->design_catalog_id : null,
            'name' => (string) $booking->customer_name,
            'customer_phone' => (string) ($booking->customer_phone ?? ''),
            'customer_email' => (string) ($booking->customer_email ?? ''),
            'date' => $booking->booking_date?->format('j M Y') ?? '-',
            'time' => $booking->start_at?->format('H:i') ?? '-',
            'booking_date_iso' => $booking->booking_date?->toDateString(),
            'start_time' => $booking->start_at?->format('H:i') ?? '',
            'status' => $status,
            'status_raw' => $statusValue,
            'payment' => $paymentLabel,
            'payment_status' => $paymentStatus,
            'pkg' => (string) ($booking->package?->name ?? '-'),
            'design_name' => (string) ($booking->designCatalog?->name ?? '-'),
            'amount' => $amount,
            'amount_text' => $amount > 0 ? $this->formatRupiah($amount) : '-',
            'total_amount' => $effectiveTotalAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'notes' => (string) ($booking->notes ?? ''),
            'payment_reference' => (string) ($booking->payment_reference ?? ''),
            'transfer_proof_url' => (string) $transferProofUrl,
            'transfer_proof_file_name' => $transferProofExists ? basename($transferProofPath) : '',
            'transfer_proof_uploaded_at' => $transferProofUploadedAt?->toIso8601String(),
            'transfer_proof_uploaded_at_text' => $transferProofUploadedAt?->format('d M Y H:i') ?? '',
            'transaction_id' => $booking->transaction?->id ? (int) $booking->transaction->id : null,
            'can_confirm_booking' => $canConfirmBooking,
            'can_confirm_payment' => $canConfirmPayment,
            'can_decline_booking' => $canDeclineBooking,
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

    protected function paymentLabel(Booking $booking, ?float $effectiveTotalAmount = null, ?float $effectivePaidAmount = null): string
    {
        $total = max(
            (float) ($effectiveTotalAmount ?? 0),
            (float) $booking->total_amount,
            (float) ($booking->transaction?->total_amount ?? 0),
            0,
        );
        $paid = max(
            (float) ($effectivePaidAmount ?? 0),
            (float) $booking->paid_amount,
            (float) ($booking->transaction?->paid_amount ?? 0),
            0,
        );

        if ($paid <= 0) {
            return '-';
        }

        if ($total > 0 && $paid >= $total) {
            return 'Full';
        }

        return 'DP';
    }

    protected function resolveBookingPaymentStatus(Booking $booking, ?float $effectiveTotalAmount = null, ?float $effectivePaidAmount = null): string
    {
        if ($booking->transaction?->status?->value) {
            $transactionStatus = (string) $booking->transaction->status->value;

            if ($transactionStatus === TransactionStatus::Paid->value && max(
                (float) ($effectiveTotalAmount ?? 0),
                (float) $booking->total_amount,
                (float) ($booking->transaction?->total_amount ?? 0),
                0,
            ) <= 0) {
                return TransactionStatus::Unpaid->value;
            }

            return $transactionStatus;
        }

        $total = max(
            (float) ($effectiveTotalAmount ?? 0),
            (float) $booking->total_amount,
            (float) ($booking->transaction?->total_amount ?? 0),
            0,
        );
        $paid = max(
            (float) ($effectivePaidAmount ?? 0),
            (float) $booking->paid_amount,
            (float) ($booking->transaction?->paid_amount ?? 0),
            0,
        );

        if ($paid <= 0) {
            return TransactionStatus::Unpaid->value;
        }

        if ($total > 0 && $paid < $total) {
            return TransactionStatus::Partial->value;
        }

        return TransactionStatus::Paid->value;
    }

    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    private function normalizePublicDiskPath(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($normalized, 'public/')) {
            return trim(substr($normalized, 7), '/');
        }

        if (str_starts_with($normalized, 'storage/')) {
            return trim(substr($normalized, 8), '/');
        }

        return $normalized;
    }

    private function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
