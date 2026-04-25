<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\QueueStatus;
use App\Enums\TransactionStatus;
use App\Models\Branch;
use App\Models\AddOn;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\DesignCatalog;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PrinterSetting;
use App\Models\QueueTicket;
use App\Models\TimeSlot;
use App\Models\Transaction;
use App\Models\User;
use App\Models\BlackoutDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class AdminDashboardDataService
{
    public function __construct(
        private readonly AdminQueuePageService $adminQueuePageService,
    ) {}

    public function bootstrapPayload(
        string $search = '',
        string $status = 'all',
        int $perPage = 15,
        string $sortBy = 'date_time',
        string $sortDir = 'desc',
    ): array
    {
        return array_merge(
            $this->snapshot(),
            $this->rowsPayload($search, $status, $perPage, $sortBy, $sortDir),
        );
    }

    public function snapshot(): array
    {
        return [
            'initialStats' => $this->stats(),
            'summaryCards' => $this->summaryCards(),
            'revenueOverview' => $this->revenueOverview(),
            'ownerHighlights' => $this->ownerHighlights(),
            'ownerModules' => $this->ownerModules(),
            'queueLive' => $this->queueLive(),
            'queueBookingOptions' => $this->queueBookingOptions(),
            'recentTransactions' => $this->recentTransactions(),
            'recentActivities' => $this->recentActivities(),
            'queueSnapshot' => $this->queueSnapshot(),
            'initialPackages' => $this->packageManagementRows(),
            'initialAddOns' => $this->addOnManagementRows(),
            'initialDesigns' => $this->designManagementRows(),
            'initialUsers' => $this->userManagementRows(),
            'initialUserRoles' => $this->userRoleOptions(),
            'initialBookingOptions' => $this->bookingFormOptions(),
            'initialBranches' => $this->branchManagementRows(),
            'initialTimeSlots' => $this->timeSlotManagementRows(),
            'initialBlackoutDates' => $this->blackoutDateManagementRows(),
            'initialPrinterSettings' => $this->printerSettingManagementRows(),
            'initialPayments' => $this->paymentManagementRows(),
            'initialPaymentTransactionOptions' => $this->paymentTransactionOptions(),
        ];
    }

    public function bookingFormOptions(): array
    {
        return [
            'branches' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Branch $branch): array => [
                    'id' => (int) $branch->id,
                    'name' => (string) $branch->name,
                ])
                ->values()
                ->all(),
            'packages' => Package::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'branch_id', 'name', 'duration_minutes', 'base_price', 'sample_photos'])
                ->map(function (Package $package): array {
                    $price = (float) $package->base_price;

                    return [
                        'id' => (int) $package->id,
                        'branch_id' => $package->branch_id ? (int) $package->branch_id : null,
                        'name' => (string) $package->name,
                        'duration_minutes' => (int) $package->duration_minutes,
                        'base_price' => $price,
                        'base_price_text' => $this->formatRupiah($price),
                        'sample_photos' => $package->resolvedSamplePhotos(),
                    ];
                })
                ->values()
                ->all(),
            'designs' => DesignCatalog::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'package_id', 'name'])
                ->map(fn (DesignCatalog $design): array => [
                    'id' => (int) $design->id,
                    'package_id' => $design->package_id ? (int) $design->package_id : null,
                    'name' => (string) $design->name,
                ])
                ->values()
                ->all(),
            'payment_methods' => collect(PaymentMethod::cases())
                ->map(fn (PaymentMethod $method): array => [
                    'value' => $method->value,
                    'label' => strtoupper($method->value),
                ])
                ->values()
                ->all(),
            'add_ons' => AddOn::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'package_id', 'code', 'name', 'price', 'max_qty', 'is_physical', 'available_stock', 'low_stock_threshold'])
                ->map(function (AddOn $addOn): array {
                    $price = (float) $addOn->price;

                    return [
                        'id' => (int) $addOn->id,
                        'package_id' => $addOn->package_id ? (int) $addOn->package_id : null,
                        'code' => (string) $addOn->code,
                        'name' => (string) $addOn->name,
                        'price' => $price,
                        'max_qty' => max(1, (int) $addOn->max_qty),
                        'is_physical' => (bool) $addOn->is_physical,
                        'available_stock' => max(0, (int) $addOn->available_stock),
                        'low_stock_threshold' => max(0, (int) $addOn->low_stock_threshold),
                        'price_text' => $this->formatRupiah($price),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function packageManagementRows(): array
    {
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        return Package::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with([
                'addOns:id,package_id,code,name,description,price,max_qty,is_active,sort_order',
            ])
            ->withCount([
                'bookings as total_bookings',
                'bookings as this_month_bookings' => function (Builder $query) use ($startOfMonth, $endOfMonth): void {
                    $query->whereBetween('booking_date', [$startOfMonth, $endOfMonth]);
                },
                'bookings as pending_bookings' => function (Builder $query): void {
                    $query->whereIn('status', [
                        BookingStatus::Pending->value,
                        BookingStatus::Confirmed->value,
                        BookingStatus::Paid->value,
                        BookingStatus::CheckedIn->value,
                        BookingStatus::InQueue->value,
                        BookingStatus::InSession->value,
                    ]);
                },
                'bookings as completed_bookings' => function (Builder $query): void {
                    $query->where('status', BookingStatus::Done->value);
                },
                'addOns as add_ons_count',
            ])
            ->withSum([
                'bookings as this_month_revenue' => function (Builder $query) use ($startOfMonth, $endOfMonth): void {
                    $query->whereBetween('booking_date', [$startOfMonth, $endOfMonth]);
                },
            ], 'paid_amount')
            ->get([
                'id',
                'branch_id',
                'code',
                'name',
                'description',
                'sample_photos',
                'duration_minutes',
                'base_price',
                'is_active',
                'sort_order',
                'created_at',
                'updated_at',
            ])
            ->map(function (Package $package): array {
                $addOns = $package->addOns
                    ->sortBy([['sort_order', 'asc'], ['name', 'asc']])
                    ->values()
                    ->map(function (AddOn $addOn): array {
                        $price = (float) $addOn->price;

                        return [
                            'id' => (int) $addOn->id,
                            'code' => (string) $addOn->code,
                            'name' => (string) $addOn->name,
                            'description' => (string) ($addOn->description ?? ''),
                            'price' => $price,
                            'price_text' => $this->formatRupiah($price),
                            'max_qty' => max(1, (int) $addOn->max_qty),
                            'is_active' => (bool) $addOn->is_active,
                            'sort_order' => (int) $addOn->sort_order,
                        ];
                    })
                    ->all();

                return [
                    'id' => (int) $package->id,
                    'branch_id' => $package->branch_id ? (int) $package->branch_id : null,
                    'code' => (string) $package->code,
                    'name' => (string) $package->name,
                    'description' => (string) ($package->description ?? ''),
                    'sample_photos' => $package->resolvedSamplePhotos(),
                    'duration_minutes' => (int) $package->duration_minutes,
                    'base_price' => (float) $package->base_price,
                    'base_price_text' => $this->formatRupiah((float) $package->base_price),
                    'is_active' => (bool) $package->is_active,
                    'sort_order' => (int) $package->sort_order,
                    'total_bookings' => (int) ($package->total_bookings ?? 0),
                    'this_month_bookings' => (int) ($package->this_month_bookings ?? 0),
                    'pending_bookings' => (int) ($package->pending_bookings ?? 0),
                    'completed_bookings' => (int) ($package->completed_bookings ?? 0),
                    'add_ons_count' => (int) ($package->add_ons_count ?? count($addOns)),
                    'add_ons' => $addOns,
                    'this_month_revenue' => (float) ($package->this_month_revenue ?? 0),
                    'this_month_revenue_text' => $this->formatRupiah((float) ($package->this_month_revenue ?? 0)),
                    'created_at' => $package->created_at?->toIso8601String(),
                    'updated_at' => $package->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function designManagementRows(): array
    {
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->toDateString();

        return DesignCatalog::query()
            ->with(['package:id,name'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount([
                'bookings as total_bookings',
                'bookings as this_month_bookings' => function (Builder $query) use ($startOfMonth, $endOfMonth): void {
                    $query->whereBetween('booking_date', [$startOfMonth, $endOfMonth]);
                },
            ])
            ->get([
                'id',
                'package_id',
                'code',
                'name',
                'theme',
                'preview_url',
                'is_active',
                'sort_order',
                'created_at',
                'updated_at',
            ])
            ->map(function (DesignCatalog $design): array {
                return [
                    'id' => (int) $design->id,
                    'package_id' => $design->package_id ? (int) $design->package_id : null,
                    'package_name' => (string) ($design->package?->name ?? '-'),
                    'code' => (string) $design->code,
                    'name' => (string) $design->name,
                    'theme' => (string) ($design->theme ?? ''),
                    'preview_url' => (string) ($design->preview_url ?? ''),
                    'is_active' => (bool) $design->is_active,
                    'sort_order' => (int) $design->sort_order,
                    'total_bookings' => (int) ($design->total_bookings ?? 0),
                    'this_month_bookings' => (int) ($design->this_month_bookings ?? 0),
                    'created_at' => $design->created_at?->toIso8601String(),
                    'updated_at' => $design->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function addOnManagementRows(): array
    {
        return AddOn::query()
            ->with(['package:id,name'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'package_id',
                'code',
                'name',
                'description',
                'price',
                'max_qty',
                'is_physical',
                'available_stock',
                'low_stock_threshold',
                'is_active',
                'sort_order',
                'created_at',
                'updated_at',
            ])
            ->map(function (AddOn $addOn): array {
                $price = (float) $addOn->price;

                return [
                    'id' => (int) $addOn->id,
                    'package_id' => $addOn->package_id ? (int) $addOn->package_id : null,
                    'package_name' => (string) ($addOn->package?->name ?? 'Global'),
                    'code' => (string) $addOn->code,
                    'name' => (string) $addOn->name,
                    'description' => (string) ($addOn->description ?? ''),
                    'price' => $price,
                    'price_text' => $this->formatRupiah($price),
                    'max_qty' => max(1, (int) $addOn->max_qty),
                    'is_physical' => (bool) $addOn->is_physical,
                    'available_stock' => max(0, (int) $addOn->available_stock),
                    'low_stock_threshold' => max(0, (int) $addOn->low_stock_threshold),
                    'type_label' => $addOn->is_physical ? 'Physical' : 'Non-physical',
                    'is_active' => (bool) $addOn->is_active,
                    'sort_order' => (int) $addOn->sort_order,
                    'created_at' => $addOn->created_at?->toIso8601String(),
                    'updated_at' => $addOn->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function userManagementRows(): array
    {
        return User::query()
            ->with(['roles:id,name'])
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'phone',
                'is_active',
                'last_login_at',
                'created_at',
                'updated_at',
            ])
            ->map(function (User $user): array {
                $roleName = (string) ($user->roles->first()?->name ?? 'staff');

                return [
                    'id' => (int) $user->id,
                    'name' => (string) $user->name,
                    'email' => (string) $user->email,
                    'phone' => (string) ($user->phone ?? ''),
                    'role' => ucfirst($roleName),
                    'role_key' => strtolower($roleName),
                    'status' => $user->is_active ? 'active' : 'inactive',
                    'is_active' => (bool) $user->is_active,
                    'source' => 'database',
                    'last_login_at' => $user->last_login_at?->toIso8601String(),
                    'created_at' => $user->created_at?->toIso8601String(),
                    'updated_at' => $user->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function userRoleOptions(): array
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['name'])
            ->map(function (Role $role): array {
                $name = (string) $role->name;

                return [
                    'value' => $name,
                    'label' => ucfirst($name),
                ];
            })
            ->values()
            ->all();
    }

    public function branchManagementRows(): array
    {
        return Branch::query()
            ->withCount(['bookings', 'timeSlots', 'transactions', 'queueTickets'])
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'timezone', 'phone', 'address', 'is_active', 'created_at', 'updated_at'])
            ->map(function (Branch $branch): array {
                return [
                    'id' => (int) $branch->id,
                    'code' => (string) $branch->code,
                    'name' => (string) $branch->name,
                    'timezone' => (string) $branch->timezone,
                    'phone' => (string) ($branch->phone ?? ''),
                    'address' => (string) ($branch->address ?? ''),
                    'is_active' => (bool) $branch->is_active,
                    'bookings_count' => (int) ($branch->bookings_count ?? 0),
                    'time_slots_count' => (int) ($branch->time_slots_count ?? 0),
                    'transactions_count' => (int) ($branch->transactions_count ?? 0),
                    'queue_tickets_count' => (int) ($branch->queue_tickets_count ?? 0),
                    'created_at' => $branch->created_at?->toIso8601String(),
                    'updated_at' => $branch->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function timeSlotManagementRows(): array
    {
        return TimeSlot::query()
            ->with('branch:id,name')
            ->orderByDesc('slot_date')
            ->orderBy('start_time')
            ->get(['id', 'branch_id', 'slot_date', 'start_time', 'end_time', 'capacity', 'is_bookable', 'created_at', 'updated_at'])
            ->map(function (TimeSlot $slot): array {
                return [
                    'id' => (int) $slot->id,
                    'branch_id' => (int) $slot->branch_id,
                    'branch_name' => (string) ($slot->branch?->name ?? '-'),
                    'slot_date' => $slot->slot_date?->toDateString(),
                    'slot_date_text' => $slot->slot_date?->format('d M Y') ?? '-',
                    'start_time' => (string) $slot->start_time,
                    'start_time_text' => substr((string) $slot->start_time, 0, 5),
                    'end_time' => (string) $slot->end_time,
                    'end_time_text' => substr((string) $slot->end_time, 0, 5),
                    'capacity' => (int) $slot->capacity,
                    'is_bookable' => (bool) $slot->is_bookable,
                    'created_at' => $slot->created_at?->toIso8601String(),
                    'updated_at' => $slot->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function blackoutDateManagementRows(): array
    {
        return BlackoutDate::query()
            ->with('branch:id,name')
            ->orderByDesc('blackout_date')
            ->get(['id', 'branch_id', 'blackout_date', 'reason', 'is_closed', 'created_at', 'updated_at'])
            ->map(function (BlackoutDate $blackout): array {
                return [
                    'id' => (int) $blackout->id,
                    'branch_id' => (int) $blackout->branch_id,
                    'branch_name' => (string) ($blackout->branch?->name ?? '-'),
                    'blackout_date' => $blackout->blackout_date?->toDateString(),
                    'blackout_date_text' => $blackout->blackout_date?->format('d M Y') ?? '-',
                    'reason' => (string) ($blackout->reason ?? ''),
                    'is_closed' => (bool) $blackout->is_closed,
                    'created_at' => $blackout->created_at?->toIso8601String(),
                    'updated_at' => $blackout->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function printerSettingManagementRows(): array
    {
        return PrinterSetting::query()
            ->with('branch:id,name')
            ->orderByDesc('is_default')
            ->orderBy('device_name')
            ->get([
                'id',
                'branch_id',
                'device_name',
                'printer_type',
                'connection',
                'paper_width_mm',
                'is_default',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->map(function (PrinterSetting $setting): array {
                return [
                    'id' => (int) $setting->id,
                    'branch_id' => (int) $setting->branch_id,
                    'branch_name' => (string) ($setting->branch?->name ?? '-'),
                    'device_name' => (string) $setting->device_name,
                    'printer_type' => (string) $setting->printer_type,
                    'connection' => is_array($setting->connection) ? $setting->connection : [],
                    'paper_width_mm' => (int) $setting->paper_width_mm,
                    'is_default' => (bool) $setting->is_default,
                    'is_active' => (bool) $setting->is_active,
                    'created_at' => $setting->created_at?->toIso8601String(),
                    'updated_at' => $setting->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function paymentManagementRows(): array
    {
        return Payment::query()
            ->with([
                'transaction:id,transaction_code,branch_id,booking_id,total_amount,paid_amount,status',
                'transaction.branch:id,name',
                'transaction.booking:id,customer_name',
                'cashier:id,name',
            ])
            ->orderByDesc('paid_at')
            ->limit(150)
            ->get(['id', 'transaction_id', 'payment_code', 'method', 'amount', 'reference_no', 'paid_at', 'cashier_id', 'created_at', 'updated_at'])
            ->map(function (Payment $payment): array {
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
                ];
            })
            ->values()
            ->all();
    }

    public function paymentTransactionOptions(): array
    {
        return Transaction::query()
            ->with(['branch:id,name', 'booking:id,customer_name'])
            ->whereIn('status', [TransactionStatus::Unpaid->value, TransactionStatus::Partial->value])
            ->orderByDesc('created_at')
            ->limit(100)
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
                    'remaining_amount_text' => $this->formatRupiah($remaining),
                ];
            })
            ->values()
            ->all();
    }

    public function rowsPayload(
        string $search = '',
        string $status = 'all',
        int $perPage = 15,
        string $sortBy = 'date_time',
        string $sortDir = 'desc',
    ): array
    {
        $paginator = $this->paginatedRows($search, $status, $perPage, $sortBy, $sortDir);

        return [
            'rows' => $paginator->items(),
            'pagination' => $this->paginationMeta($paginator),
            'initialRows' => $paginator->items(),
            'initialPagination' => $this->paginationMeta($paginator),
            'pendingBookingsCount' => $this->pendingBookingsCount(),
        ];
    }

    protected function pendingBookingsCount(): int
    {
        return Booking::query()
            ->where('status', BookingStatus::Pending->value)
            ->count();
    }

    public function summaryCards(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->copy()->subDay()->toDateString();

        $todayBookings = Booking::query()->whereDate('booking_date', $today)->count();
        $yesterdayBookings = Booking::query()->whereDate('booking_date', $yesterday)->count();

        $todayRevenue = $this->dailyRevenueTotal($today);
        $yesterdayRevenue = $this->dailyRevenueTotal($yesterday);

        $waitingToday = QueueTicket::query()
            ->whereDate('queue_date', $today)
            ->where('status', QueueStatus::Waiting->value)
            ->count();

        $waitingYesterday = QueueTicket::query()
            ->whereDate('queue_date', $yesterday)
            ->where('status', QueueStatus::Waiting->value)
            ->count();

        $thisMonthStart = now()->copy()->startOfMonth()->toDateString();
        $thisMonthEnd = now()->toDateString();
        $prevMonthStart = now()->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $prevMonthEnd = now()->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $thisMonthTotalBookings = Booking::query()
            ->whereBetween('booking_date', [$thisMonthStart, $thisMonthEnd])
            ->count();

        $thisMonthConvertedBookings = Booking::query()
            ->whereBetween('booking_date', [$thisMonthStart, $thisMonthEnd])
            ->whereIn('status', [BookingStatus::Paid->value, BookingStatus::Done->value])
            ->count();

        $prevMonthTotalBookings = Booking::query()
            ->whereBetween('booking_date', [$prevMonthStart, $prevMonthEnd])
            ->count();

        $prevMonthConvertedBookings = Booking::query()
            ->whereBetween('booking_date', [$prevMonthStart, $prevMonthEnd])
            ->whereIn('status', [BookingStatus::Paid->value, BookingStatus::Done->value])
            ->count();

        $thisMonthConversion = $thisMonthTotalBookings > 0
            ? round(($thisMonthConvertedBookings / $thisMonthTotalBookings) * 100)
            : 0;

        $prevMonthConversion = $prevMonthTotalBookings > 0
            ? round(($prevMonthConvertedBookings / $prevMonthTotalBookings) * 100)
            : 0;

        $sevenDaySeries = $this->buildRevenueSeries(7, 'D');

        $bookingSparkline = array_map(
            fn (array $point): float => (float) $point['bookings'],
            $sevenDaySeries,
        );

        $revenueSparkline = array_map(
            fn (array $point): float => round(((float) $point['revenue']) / 1000000, 2),
            $sevenDaySeries,
        );

        $queueSparkline = $this->buildQueueWaitingSparkline(7);
        $conversionSparkline = $this->buildConversionSparkline(7);

        $bookingsDelta = $todayBookings - $yesterdayBookings;
        $queueDelta = $waitingToday - $waitingYesterday;
        $revenueDelta = $this->percentageChange($todayRevenue, $yesterdayRevenue);
        $conversionDelta = $thisMonthConversion - $prevMonthConversion;

        return [
            [
                'title' => "Today's Bookings",
                'value' => number_format($todayBookings),
                'change' => $this->signedNumber($bookingsDelta),
                'changeLabel' => 'from yesterday',
                'trend' => $this->trendFromDelta((float) $bookingsDelta),
                'accent' => '#2563EB',
                'accentLight' => '#EFF6FF',
                'accentBorder' => '#DBEAFE',
                'sparkline' => $bookingSparkline,
            ],
            [
                'title' => 'Total Revenue',
                'value' => $this->formatRupiah($todayRevenue),
                'change' => $this->signedPercent($revenueDelta),
                'changeLabel' => 'from yesterday',
                'trend' => $this->trendFromDelta($revenueDelta),
                'accent' => '#059669',
                'accentLight' => '#ECFDF5',
                'accentBorder' => '#A7F3D0',
                'sparkline' => $revenueSparkline,
            ],
            [
                'title' => 'Active Queue',
                'value' => number_format($waitingToday),
                'change' => $this->signedNumber($queueDelta),
                'changeLabel' => 'waiting now',
                'trend' => $this->trendFromDelta((float) $queueDelta),
                'accent' => '#D97706',
                'accentLight' => '#FFFBEB',
                'accentBorder' => '#FDE68A',
                'sparkline' => $queueSparkline,
            ],
            [
                'title' => 'Conversion Rate',
                'value' => $thisMonthConversion . '%',
                'change' => $this->signedPercent((float) $conversionDelta),
                'changeLabel' => 'this month',
                'trend' => $this->trendFromDelta((float) $conversionDelta),
                'accent' => '#7C3AED',
                'accentLight' => '#F5F3FF',
                'accentBorder' => '#DDD6FE',
                'sparkline' => $conversionSparkline,
            ],
        ];
    }

    public function revenueOverview(): array
    {
        return [
            '7d' => $this->buildRevenueSeries(7, 'D'),
            '30d' => $this->buildRevenueSeries(30, 'j M'),
        ];
    }

    public function queueLive(): array
    {
        return $this->adminQueuePageService->live();
    }

    public function queueBookingOptions(?string $queueDate = null): array
    {
        return $this->adminQueuePageService->bookingOptions($queueDate);
    }

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

    public function paginatedRows(
        string $search = '',
        string $status = 'all',
        int $perPage = 15,
        string $sortBy = 'date_time',
        string $sortDir = 'desc',
    ): LengthAwarePaginator
    {
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

    public function reportSummary(
        ?Carbon $from = null,
        ?Carbon $to = null,
        ?int $packageId = null,
        ?int $cashierId = null,
    ): array {
        [$start, $end] = $this->normalizeDateRange($from, $to);
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();
        $revenueStatuses = $this->revenueTransactionStatuses();

        $selectedPackage = $packageId
            ? Package::query()->find($packageId, ['id', 'name'])
            : null;

        $selectedCashier = $cashierId
            ? User::query()->find($cashierId, ['id', 'name'])
            : null;

        $transactionBase = Transaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', $revenueStatuses)
            ->when($cashierId !== null, function (Builder $query) use ($cashierId): void {
                $query->where('cashier_id', $cashierId);
            })
            ->when($packageId !== null, function (Builder $query) use ($packageId): void {
                $query->whereHas('booking', function (Builder $bookingQuery) use ($packageId): void {
                    $bookingQuery->where('package_id', $packageId);
                });
            });

        $totalRevenue = (float) (clone $transactionBase)->sum('paid_amount');
        $transactionCount = (int) (clone $transactionBase)->count();
        $averageTransaction = $transactionCount > 0
            ? round($totalRevenue / $transactionCount, 2)
            : 0.0;

        $bookingBase = Booking::query()
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->when($packageId !== null, function (Builder $query) use ($packageId): void {
                $query->where('package_id', $packageId);
            });

        $statusCountMap = (clone $bookingBase)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $bookingStatuses = collect(BookingStatus::cases())
            ->map(function (BookingStatus $status) use ($statusCountMap): array {
                return [
                    'status' => $status->value,
                    'label' => $this->statusLabel($status->value),
                    'count' => (int) ($statusCountMap[$status->value] ?? 0),
                ];
            })
            ->values()
            ->all();

        $totalBookings = (int) array_sum(array_column($bookingStatuses, 'count'));

        $convertedBookings = (int) (clone $bookingBase)
            ->whereIn('status', [BookingStatus::Paid->value, BookingStatus::Done->value])
            ->count();

        $conversionRate = $totalBookings > 0
            ? round(($convertedBookings / $totalBookings) * 100, 2)
            : 0.0;

        $packagePopularity = Booking::query()
            ->with('package:id,name')
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->when($packageId !== null, function (Builder $query) use ($packageId): void {
                $query->where('package_id', $packageId);
            })
            ->selectRaw('package_id, COUNT(*) as booking_count, SUM(paid_amount) as total_revenue')
            ->groupBy('package_id')
            ->orderByDesc('booking_count')
            ->get()
            ->map(function (Booking $aggregate): array {
                $revenue = (float) ($aggregate->total_revenue ?? 0);

                return [
                    'package_id' => (int) $aggregate->package_id,
                    'package_name' => (string) ($aggregate->package?->name ?? '-'),
                    'booking_count' => (int) ($aggregate->booking_count ?? 0),
                    'revenue' => $revenue,
                    'revenue_text' => $this->formatRupiah($revenue),
                ];
            })
            ->values()
            ->all();

        $cashierPerformance = Transaction::query()
            ->with('cashier:id,name')
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', $revenueStatuses)
            ->when($cashierId !== null, function (Builder $query) use ($cashierId): void {
                $query->where('cashier_id', $cashierId);
            })
            ->when($packageId !== null, function (Builder $query) use ($packageId): void {
                $query->whereHas('booking', function (Builder $bookingQuery) use ($packageId): void {
                    $bookingQuery->where('package_id', $packageId);
                });
            })
            ->selectRaw('cashier_id, COUNT(*) as transaction_count, SUM(paid_amount) as total_revenue')
            ->groupBy('cashier_id')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function (Transaction $aggregate): array {
                $revenue = (float) ($aggregate->total_revenue ?? 0);
                $totalTransactions = (int) ($aggregate->transaction_count ?? 0);
                $averageTicket = $totalTransactions > 0
                    ? round($revenue / $totalTransactions, 2)
                    : 0.0;

                return [
                    'cashier_id' => (int) $aggregate->cashier_id,
                    'cashier_name' => (string) ($aggregate->cashier?->name ?? '-'),
                    'transaction_count' => $totalTransactions,
                    'revenue' => $revenue,
                    'revenue_text' => $this->formatRupiah($revenue),
                    'average_transaction' => $averageTicket,
                    'average_transaction_text' => $this->formatRupiah($averageTicket),
                ];
            })
            ->values()
            ->all();

        $addOnPerformance = AddOn::query()
            ->join('booking_add_ons as booking_add_on', 'booking_add_on.add_on_id', '=', 'add_ons.id')
            ->join('bookings as booking', 'booking.id', '=', 'booking_add_on.booking_id')
            ->whereBetween('booking.booking_date', [$startDate, $endDate])
            ->where('booking.status', '!=', BookingStatus::Cancelled->value)
            ->when($packageId !== null, function (Builder $query) use ($packageId): void {
                $query->where('booking.package_id', $packageId);
            })
            ->when($cashierId !== null, function (Builder $query) use ($cashierId): void {
                $query->whereExists(function ($exists) use ($cashierId): void {
                    $exists->selectRaw('1')
                        ->from('transactions as transaction')
                        ->whereColumn('transaction.booking_id', 'booking.id')
                        ->where('transaction.cashier_id', $cashierId);
                });
            })
            ->selectRaw('
                add_ons.id as add_on_id,
                add_ons.code as add_on_code,
                add_ons.name as add_on_name,
                COUNT(DISTINCT booking.id) as booking_count,
                SUM(booking_add_on.qty) as total_qty,
                SUM(booking_add_on.line_total) as total_revenue
            ')
            ->groupBy('add_ons.id', 'add_ons.code', 'add_ons.name')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_revenue')
            ->limit(20)
            ->get()
            ->map(function (AddOn $aggregate): array {
                $revenue = (float) ($aggregate->total_revenue ?? 0);

                return [
                    'add_on_id' => (int) $aggregate->add_on_id,
                    'add_on_code' => (string) ($aggregate->add_on_code ?? ''),
                    'add_on_name' => (string) ($aggregate->add_on_name ?? '-'),
                    'booking_count' => (int) ($aggregate->booking_count ?? 0),
                    'total_qty' => (int) ($aggregate->total_qty ?? 0),
                    'total_revenue' => $revenue,
                    'total_revenue_text' => $this->formatRupiah($revenue),
                ];
            })
            ->values()
            ->all();

        $availableAddOnsQuery = AddOn::query()
            ->where('is_active', true);

        if ($packageId !== null) {
            $availableAddOnsQuery->where(function (Builder $query) use ($packageId): void {
                $query->whereNull('package_id')
                    ->orWhere('package_id', $packageId);
            });
        }

        $availableAddOnsCount = (int) (clone $availableAddOnsQuery)->count();
        $availableGlobalAddOnsCount = (int) (clone $availableAddOnsQuery)
            ->whereNull('package_id')
            ->count();
        $availablePackageSpecificAddOnsCount = (int) (clone $availableAddOnsQuery)
            ->whereNotNull('package_id')
            ->count();

        $dailyRevenueMap = (clone $transactionBase)
            ->selectRaw('DATE(created_at) as period, COUNT(*) as total_transactions, SUM(paid_amount) as total_revenue')
            ->groupBy('period')
            ->get()
            ->keyBy('period');

        $dailyBookingsMap = (clone $bookingBase)
            ->selectRaw('DATE(booking_date) as period, COUNT(*) as total')
            ->groupBy('period')
            ->pluck('total', 'period');

        $dailyWalkInMap = $packageId === null
            ? Transaction::query()
                ->whereNull('booking_id')
                ->whereBetween('created_at', [$start, $end])
                ->whereIn('status', $revenueStatuses)
                ->when($cashierId !== null, function (Builder $query) use ($cashierId): void {
                    $query->where('cashier_id', $cashierId);
                })
                ->selectRaw('DATE(created_at) as period, COUNT(*) as total')
                ->groupBy('period')
                ->pluck('total', 'period')
            : collect();

        $cursor = $start->copy()->startOfDay();
        $dailySummary = [];

        while ($cursor->lte($end)) {
            $periodKey = $cursor->toDateString();
            $revenueRow = $dailyRevenueMap->get($periodKey);
            $revenue = (float) ($revenueRow->total_revenue ?? 0);

            $dailySummary[] = [
                'date' => $periodKey,
                'label' => $cursor->translatedFormat('j M'),
                'revenue' => $revenue,
                'revenue_text' => $this->formatRupiah($revenue),
                'bookings' => (int) ($dailyBookingsMap[$periodKey] ?? 0),
                'walk_ins' => (int) ($dailyWalkInMap[$periodKey] ?? 0),
                'transactions' => (int) ($revenueRow->total_transactions ?? 0),
            ];

            $cursor->addDay();
        }

        return [
            'range' => [
                'from' => $startDate,
                'to' => $endDate,
                'label' => $start->translatedFormat('j M Y') . ' - ' . $end->translatedFormat('j M Y'),
                'days' => $start->diffInDays($end) + 1,
            ],
            'filters' => [
                'package_id' => $packageId,
                'package_name' => $selectedPackage?->name,
                'cashier_id' => $cashierId,
                'cashier_name' => $selectedCashier?->name,
            ],
            'revenue_summary' => [
                'total_revenue' => $totalRevenue,
                'total_revenue_text' => $this->formatRupiah($totalRevenue),
                'transaction_count' => $transactionCount,
                'average_transaction' => $averageTransaction,
                'average_transaction_text' => $this->formatRupiah($averageTransaction),
            ],
            'booking_summary' => [
                'total_bookings' => $totalBookings,
                'converted_bookings' => $convertedBookings,
                'conversion_rate' => $conversionRate,
                'conversion_rate_text' => number_format($conversionRate, 2) . '%',
                'statuses' => $bookingStatuses,
            ],
            'add_on_summary' => [
                'available_count' => $availableAddOnsCount,
                'available_count_text' => number_format($availableAddOnsCount),
                'global_count' => $availableGlobalAddOnsCount,
                'global_count_text' => number_format($availableGlobalAddOnsCount),
                'package_specific_count' => $availablePackageSpecificAddOnsCount,
                'package_specific_count_text' => number_format($availablePackageSpecificAddOnsCount),
            ],
            'package_popularity' => $packagePopularity,
            'cashier_performance' => $cashierPerformance,
            'add_on_performance' => $addOnPerformance,
            'daily_summary' => $dailySummary,
        ];
    }

    public function ownerHighlights(): array
    {
        $today = now()->toDateString();

        return [
            [
                'label' => 'Pendapatan Hari Ini',
                'value' => $this->formatRupiah((float) Transaction::query()->whereDate('created_at', $today)->sum('paid_amount')),
                'helper' => 'Akumulasi pembayaran transaksi hari ini',
                'tone' => 'blue',
                'icon' => 'cash',
            ],
            [
                'label' => 'Booking Aktif Hari Ini',
                'value' => number_format(
                    Booking::query()
                        ->whereDate('booking_date', $today)
                        ->whereIn('status', BookingStatus::activeStatuses())
                        ->count()
                ),
                'helper' => 'Status pending sampai in_session',
                'tone' => 'amber',
                'icon' => 'booking',
            ],
            [
                'label' => 'Antrian Menunggu',
                'value' => number_format(
                    QueueTicket::query()
                        ->whereDate('queue_date', $today)
                        ->where('status', QueueStatus::Waiting->value)
                        ->count()
                ),
                'helper' => 'Ticket waiting untuk hari ini',
                'tone' => 'purple',
                'icon' => 'queue',
            ],
            [
                'label' => 'Transaksi Belum Lunas',
                'value' => number_format(
                    Transaction::query()
                        ->whereIn('status', [TransactionStatus::Unpaid->value, TransactionStatus::Partial->value])
                        ->count()
                ),
                'helper' => 'Perlu tindak lanjut owner/cashier',
                'tone' => 'rose',
                'icon' => 'alert',
            ],
        ];
    }

    public function ownerModules(): array
    {
        return [
            [
                'label' => 'Kelola Booking',
                'description' => 'Monitoring booking, ubah status, dan cek detail pelanggan.',
                'url' => url('/admin/bookings'),
                'badge' => number_format(Booking::query()->count()),
                'tone' => 'blue',
                'icon' => 'calendar',
            ],
            [
                'label' => 'Transaksi',
                'description' => 'Pantau pembayaran dan performa transaksi harian.',
                'url' => url('/admin/transactions'),
                'badge' => number_format(Transaction::query()->count()),
                'tone' => 'emerald',
                'icon' => 'receipt',
            ],
            [
                'label' => 'Antrian Studio',
                'description' => 'Pantau antrean pelanggan aktif secara real-time.',
                'url' => url('/admin/queue'),
                'badge' => number_format(QueueTicket::query()->count()),
                'tone' => 'violet',
                'icon' => 'queue',
            ],
            [
                'label' => 'Paket',
                'description' => 'Atur paket foto, harga, durasi, dan status aktif.',
                'url' => url('/admin/packages'),
                'badge' => number_format(Package::query()->count()),
                'tone' => 'amber',
                'icon' => 'box',
            ],
            [
                'label' => 'Design Catalog',
                'description' => 'Kelola tema template dan materi desain photobooth.',
                'url' => url('/admin/design-catalogs'),
                'badge' => number_format(DesignCatalog::query()->count()),
                'tone' => 'pink',
                'icon' => 'image',
            ],
            [
                'label' => 'User Admin/Cashier',
                'description' => 'Manajemen akun owner dan cashier.',
                'url' => url('/admin/users'),
                'badge' => number_format(User::query()->count()),
                'tone' => 'slate',
                'icon' => 'users',
            ],
        ];
    }

    public function recentTransactions(): array
    {
        return Transaction::query()
            ->with([
                'cashier:id,name',
                'booking:id,customer_name',
                'payments:id,transaction_id,method',
            ])
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'transaction_code', 'cashier_id', 'booking_id', 'total_amount', 'paid_amount', 'status', 'created_at'])
            ->map(function (Transaction $transaction): array {
                $latestPaymentMethod = $transaction->payments
                    ->sortByDesc('id')
                    ->first()?->method?->value;

                return [
                    'code' => (string) $transaction->transaction_code,
                    'customer' => (string) ($transaction->booking?->customer_name ?? '-'),
                    'cashier' => (string) ($transaction->cashier?->name ?? '-'),
                    'method' => $latestPaymentMethod ? strtoupper($latestPaymentMethod) : '-',
                    'amount' => (float) ($transaction->paid_amount > 0 ? $transaction->paid_amount : $transaction->total_amount),
                    'total_text' => $this->formatRupiah((float) $transaction->total_amount),
                    'paid_text' => $this->formatRupiah((float) $transaction->paid_amount),
                    'status' => (string) $transaction->status->value,
                    'time' => $transaction->created_at?->diffForHumans() ?? '-',
                    'time_text' => $transaction->created_at?->translatedFormat('d M Y, H:i') ?? '-',
                ];
            })
            ->values()
            ->all();
    }

    public function recentActivities(): array
    {
        return ActivityLog::query()
            ->with('actor:id,name')
            ->latest('created_at')
            ->limit(6)
            ->get(['id', 'actor_id', 'action', 'module', 'created_at'])
            ->map(function (ActivityLog $log): array {
                return [
                    'actor' => (string) ($log->actor?->name ?? 'System'),
                    'action' => ucwords(str_replace('_', ' ', (string) $log->action)),
                    'module' => (string) ($log->module ?: '-'),
                    'time' => $log->created_at?->diffForHumans() ?? '-',
                ];
            })
            ->values()
            ->all();
    }

    public function queueSnapshot(): array
    {
        $live = $this->adminQueuePageService->live();
        $rows = [];
        $seenTicketIds = [];

        $current = $live['current'] ?? null;
        if (is_array($current)) {
            $currentStatus = (string) ($current['status'] ?? '');

            if (in_array($currentStatus, [
                QueueStatus::InSession->value,
                QueueStatus::Called->value,
                QueueStatus::CheckedIn->value,
            ], true)) {
                $currentTicketId = (int) ($current['ticket_id'] ?? 0);
                if ($currentTicketId > 0) {
                    $seenTicketIds[$currentTicketId] = true;
                }

                $rows[] = [
                    'queue_code' => (string) ($current['queue_code'] ?? '-'),
                    'queue_number' => (int) ($current['queue_number'] ?? 0),
                    'customer_name' => (string) ($current['customer_name'] ?? '-'),
                    'status' => $currentStatus,
                ];
            }
        }

        foreach (($live['waiting'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $ticketId = (int) ($item['ticket_id'] ?? 0);
            if ($ticketId > 0 && isset($seenTicketIds[$ticketId])) {
                continue;
            }

            $rows[] = [
                'queue_code' => (string) ($item['queue_code'] ?? '-'),
                'queue_number' => (int) ($item['queue_number'] ?? 0),
                'customer_name' => (string) ($item['customer_name'] ?? '-'),
                'status' => (string) ($item['status'] ?? QueueStatus::Waiting->value),
            ];

            if ($ticketId > 0) {
                $seenTicketIds[$ticketId] = true;
            }
        }

        return array_slice($rows, 0, 6);
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
            && $remainingAmount <= 0
            && (
                $effectiveTotalAmount <= 0
                || $paymentStatus === TransactionStatus::Paid->value
            );
        $canConfirmPayment = ! $isClosedStatus
            && $effectiveTotalAmount > 0
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
            0
        );
        $paid = max(
            (float) ($effectivePaidAmount ?? 0),
            (float) $booking->paid_amount,
            (float) ($booking->transaction?->paid_amount ?? 0),
            0
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
                0
            ) <= 0) {
                return TransactionStatus::Unpaid->value;
            }

            return $transactionStatus;
        }

        $total = max(
            (float) ($effectiveTotalAmount ?? 0),
            (float) $booking->total_amount,
            (float) ($booking->transaction?->total_amount ?? 0),
            0
        );
        $paid = max(
            (float) ($effectivePaidAmount ?? 0),
            (float) $booking->paid_amount,
            (float) ($booking->transaction?->paid_amount ?? 0),
            0
        );

        if ($paid <= 0) {
            return TransactionStatus::Unpaid->value;
        }

        if ($total > 0 && $paid < $total) {
            return TransactionStatus::Partial->value;
        }

        return TransactionStatus::Paid->value;
    }

    protected function buildRevenueSeries(int $days, string $labelFormat): array
    {
        $start = now()->copy()->subDays($days - 1)->startOfDay();
        $end = now()->copy()->endOfDay();

        $bookingMap = Booking::query()
            ->selectRaw('DATE(booking_date) as period, COUNT(*) as total')
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('period')
            ->pluck('total', 'period');

        $revenueMap = Transaction::query()
            ->selectRaw('DATE(created_at) as period, SUM(paid_amount) as total')
            ->whereIn('status', $this->revenueTransactionStatuses())
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('period')
            ->pluck('total', 'period');

        $series = [];

        for ($index = 0; $index < $days; $index++) {
            $date = $start->copy()->addDays($index);
            $periodKey = $date->toDateString();

            $series[] = [
                'key' => $periodKey,
                'label' => $this->formatPeriodLabel($date, $labelFormat),
                'revenue' => (float) ($revenueMap[$periodKey] ?? 0),
                'bookings' => (int) ($bookingMap[$periodKey] ?? 0),
            ];
        }

        return $series;
    }

    protected function buildQueueWaitingSparkline(int $days): array
    {
        $start = now()->copy()->subDays($days - 1)->startOfDay();
        $end = now()->copy()->endOfDay();

        $waitingMap = QueueTicket::query()
            ->selectRaw('DATE(queue_date) as period, COUNT(*) as total')
            ->where('status', QueueStatus::Waiting->value)
            ->whereBetween('queue_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('period')
            ->pluck('total', 'period');

        $sparkline = [];

        for ($index = 0; $index < $days; $index++) {
            $periodKey = $start->copy()->addDays($index)->toDateString();
            $sparkline[] = (float) ($waitingMap[$periodKey] ?? 0);
        }

        return $sparkline;
    }

    protected function buildConversionSparkline(int $days): array
    {
        $start = now()->copy()->subDays($days - 1)->startOfDay();
        $end = now()->copy()->endOfDay();

        $totalMap = Booking::query()
            ->selectRaw('DATE(booking_date) as period, COUNT(*) as total')
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('period')
            ->pluck('total', 'period');

        $convertedMap = Booking::query()
            ->selectRaw('DATE(booking_date) as period, COUNT(*) as total')
            ->whereBetween('booking_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', [BookingStatus::Paid->value, BookingStatus::Done->value])
            ->groupBy('period')
            ->pluck('total', 'period');

        $sparkline = [];

        for ($index = 0; $index < $days; $index++) {
            $periodKey = $start->copy()->addDays($index)->toDateString();
            $total = (int) ($totalMap[$periodKey] ?? 0);
            $converted = (int) ($convertedMap[$periodKey] ?? 0);

            $sparkline[] = $total > 0 ? round(($converted / $total) * 100, 2) : 0;
        }

        return $sparkline;
    }

    protected function dailyRevenueTotal(string $date): float
    {
        return (float) Transaction::query()
            ->whereDate('created_at', $date)
            ->whereIn('status', $this->revenueTransactionStatuses())
            ->sum('paid_amount');
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

    protected function normalizeDateRange(?Carbon $from = null, ?Carbon $to = null): array
    {
        $start = ($from ?? now()->copy()->subDays(6))->copy()->startOfDay();
        $end = ($to ?? now())->copy()->endOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    protected function revenueTransactionStatuses(): array
    {
        return [
            TransactionStatus::Paid->value,
            TransactionStatus::Partial->value,
        ];
    }

    protected function percentageChange(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    protected function signedNumber(int $value): string
    {
        return ($value > 0 ? '+' : '') . number_format($value);
    }

    protected function signedPercent(float $value, int $precision = 0): string
    {
        $rounded = round($value, $precision);

        return ($rounded > 0 ? '+' : '') . number_format($rounded, $precision) . '%';
    }

    protected function trendFromDelta(float $delta): string
    {
        if ($delta > 0) {
            return 'up';
        }

        if ($delta < 0) {
            return 'down';
        }

        return 'neutral';
    }

    protected function statusLabel(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }

    protected function formatPeriodLabel(Carbon $date, string $labelFormat): string
    {
        if ($labelFormat === 'D') {
            return $date->format('D');
        }

        return $date->translatedFormat($labelFormat);
    }

    protected function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
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
}
