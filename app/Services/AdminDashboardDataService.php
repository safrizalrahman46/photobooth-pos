<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\QueueStatus;
use App\Enums\TransactionStatus;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\DesignCatalog;
use App\Models\Package;
use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class AdminDashboardDataService
{
    public function bootstrapPayload(string $search = '', string $status = 'all', int $perPage = 15): array
    {
        return array_merge(
            $this->snapshot(),
            $this->rowsPayload($search, $status, $perPage),
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
            'recentTransactions' => $this->recentTransactions(),
            'recentActivities' => $this->recentActivities(),
            'queueSnapshot' => $this->queueSnapshot(),
        ];
    }

    public function rowsPayload(string $search = '', string $status = 'all', int $perPage = 15): array
    {
        $paginator = $this->paginatedRows($search, $status, $perPage);

        return [
            'rows' => $paginator->items(),
            'pagination' => $this->paginationMeta($paginator),
            'initialRows' => $paginator->items(),
            'initialPagination' => $this->paginationMeta($paginator),
        ];
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
        $today = now()->toDateString();

        $currentTicket = QueueTicket::query()
            ->with(['booking.package:id,name,duration_minutes'])
            ->whereDate('queue_date', $today)
            ->where('status', QueueStatus::InSession->value)
            ->latest('started_at')
            ->first([
                'id',
                'booking_id',
                'queue_code',
                'queue_number',
                'customer_name',
                'status',
                'called_at',
                'checked_in_at',
                'started_at',
            ]);

        if (! $currentTicket) {
            $currentTicket = QueueTicket::query()
                ->with(['booking.package:id,name,duration_minutes'])
                ->whereDate('queue_date', $today)
                ->whereIn('status', [QueueStatus::Called->value, QueueStatus::CheckedIn->value])
                ->orderBy('queue_number')
                ->first([
                    'id',
                    'booking_id',
                    'queue_code',
                    'queue_number',
                    'customer_name',
                    'status',
                    'called_at',
                    'checked_in_at',
                    'started_at',
                ]);
        }

        $sessionDurationSeconds = max(
            60,
            ((int) ($currentTicket?->booking?->package?->duration_minutes ?? 20)) * 60,
        );

        $startedAt = $currentTicket?->started_at
            ?? $currentTicket?->checked_in_at
            ?? $currentTicket?->called_at;

        $elapsedSeconds = $startedAt ? (int) $startedAt->diffInSeconds(now()) : 0;
        $remainingSeconds = max($sessionDurationSeconds - $elapsedSeconds, 0);
        $progressPercentage = $sessionDurationSeconds > 0
            ? min(100, round(($elapsedSeconds / $sessionDurationSeconds) * 100, 2))
            : 0;

        $waitingTickets = QueueTicket::query()
            ->with(['booking.package:id,name'])
            ->whereDate('queue_date', $today)
            ->whereIn('status', [
                QueueStatus::Waiting->value,
                QueueStatus::Called->value,
                QueueStatus::CheckedIn->value,
            ])
            ->orderBy('queue_number')
            ->limit(8)
            ->get([
                'id',
                'booking_id',
                'queue_code',
                'queue_number',
                'customer_name',
                'status',
                'created_at',
            ])
            ->map(function (QueueTicket $ticket): array {
                return [
                    'queue_code' => (string) $ticket->queue_code,
                    'queue_number' => (int) $ticket->queue_number,
                    'customer_name' => (string) $ticket->customer_name,
                    'package_name' => (string) ($ticket->booking?->package?->name ?? '-'),
                    'status' => (string) $ticket->status->value,
                    'status_label' => $this->statusLabel((string) $ticket->status->value),
                    'added_at' => $ticket->created_at?->format('H:i') ?? '-',
                ];
            })
            ->values()
            ->all();

        $queueStats = [
            'in_queue' => QueueTicket::query()
                ->whereDate('queue_date', $today)
                ->whereIn('status', [
                    QueueStatus::Waiting->value,
                    QueueStatus::Called->value,
                    QueueStatus::CheckedIn->value,
                    QueueStatus::InSession->value,
                ])
                ->count(),
            'in_session' => QueueTicket::query()
                ->whereDate('queue_date', $today)
                ->where('status', QueueStatus::InSession->value)
                ->count(),
            'waiting' => QueueTicket::query()
                ->whereDate('queue_date', $today)
                ->where('status', QueueStatus::Waiting->value)
                ->count(),
            'completed_today' => QueueTicket::query()
                ->whereDate('queue_date', $today)
                ->where('status', QueueStatus::Finished->value)
                ->count(),
        ];

        return [
            'stats' => $queueStats,
            'current' => $currentTicket ? [
                'queue_code' => (string) $currentTicket->queue_code,
                'queue_number' => (int) $currentTicket->queue_number,
                'customer_name' => (string) $currentTicket->customer_name,
                'status' => (string) $currentTicket->status->value,
                'status_label' => $this->statusLabel((string) $currentTicket->status->value),
                'package_name' => (string) ($currentTicket->booking?->package?->name ?? '-'),
                'session_duration_seconds' => (int) $sessionDurationSeconds,
                'elapsed_seconds' => (int) $elapsedSeconds,
                'remaining_seconds' => (int) $remainingSeconds,
                'progress_percentage' => (float) $progressPercentage,
            ] : null,
            'waiting' => $waitingTickets,
        ];
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
            'package_popularity' => $packagePopularity,
            'cashier_performance' => $cashierPerformance,
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
        $today = now()->toDateString();

        return QueueTicket::query()
            ->whereDate('queue_date', $today)
            ->whereIn('status', [
                QueueStatus::Waiting->value,
                QueueStatus::Called->value,
                QueueStatus::InSession->value,
            ])
            ->orderBy('queue_number')
            ->limit(6)
            ->get(['id', 'queue_code', 'queue_number', 'customer_name', 'status'])
            ->map(function (QueueTicket $ticket): array {
                return [
                    'queue_code' => (string) $ticket->queue_code,
                    'queue_number' => (int) $ticket->queue_number,
                    'customer_name' => (string) $ticket->customer_name,
                    'status' => (string) $ticket->status->value,
                ];
            })
            ->values()
            ->all();
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
}
