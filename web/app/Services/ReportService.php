<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\QueueStatus;
use App\Enums\TransactionStatus;
use App\Models\AddOn;
use App\Models\Booking;
use App\Models\Package;
use App\Models\QueueTicket;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    public function salesSummary(string $from, string $to, ?int $branchId = null): array
    {
        $baseQuery = Transaction::query()
            ->whereBetween('created_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ]);

        $bookingQuery = Booking::query()
            ->whereBetween('paid_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ])
            ->where('payment_type', 'full')
            ->where('status', 'paid');

        if ($branchId) {
            $baseQuery->where('branch_id', $branchId);
            $bookingQuery->where('branch_id', $branchId);
        }

        $grossSales = (float) (clone $baseQuery)->sum('total_amount');
        $paidSales = (float) (clone $baseQuery)->where('status', TransactionStatus::Paid)->sum('paid_amount');
        $transactionCount = (int) (clone $baseQuery)->count();
        $onlineBookingSales = (float) (clone $bookingQuery)->sum('paid_amount');

        return [
            'gross_sales' => $grossSales,
            'paid_sales' => $paidSales,
            'online_booking_sales' => $onlineBookingSales,
            'combined_paid_sales' => $paidSales + $onlineBookingSales,
            'transaction_count' => $transactionCount,
            'average_transaction' => $transactionCount > 0 ? round($grossSales / $transactionCount, 2) : 0,
        ];
    }

    public function bookingVolume(string $from, string $to, ?int $branchId = null): array
    {
        $query = Booking::query()->whereBetween('booking_date', [$from, $to]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total' => (int) (clone $query)->count(),
            'done' => (int) (clone $query)->where('status', 'done')->count(),
            'cancelled' => (int) (clone $query)->where('status', 'cancelled')->count(),
        ];
    }

    public function queueVolume(string $from, string $to, ?int $branchId = null): array
    {
        $query = QueueTicket::query()->whereBetween('queue_date', [$from, $to]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total' => (int) (clone $query)->count(),
            'finished' => (int) (clone $query)->where('status', 'finished')->count(),
            'waiting' => (int) (clone $query)->where('status', 'waiting')->count(),
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
            'cashier_daily_series' => $this->buildCashierDailySeries(
                $start,
                $end,
                $packageId,
                $cashierId,
                $revenueStatuses,
                array_column($cashierPerformance, 'cashier_id'),
            ),
            'add_on_performance' => $addOnPerformance,
            'daily_summary' => $dailySummary,
            'chart_modes' => $this->buildReportChartModes(
                $dailySummary,
                $start,
                $end,
                $bookingBase,
                $transactionBase,
                $cashierPerformance,
                $packageId,
                $cashierId,
                $revenueStatuses,
            ),
        ];
    }

    protected function buildReportChartModes(
        array $dailySummary,
        Carbon $start,
        Carbon $end,
        Builder $bookingBase,
        Builder $transactionBase,
        array $cashierPerformance,
        ?int $packageId,
        ?int $cashierId,
        array $revenueStatuses,
    ): array {
        $dailyLabels = array_map(fn (array $row): string => (string) ($row['label'] ?? '-'), $dailySummary);
        $dailyRevenue = array_map(fn (array $row): float => (float) ($row['revenue'] ?? 0), $dailySummary);
        $dailyBookings = array_map(fn (array $row): int => (int) ($row['bookings'] ?? 0), $dailySummary);
        $dailyTransactions = array_map(fn (array $row): int => (int) ($row['transactions'] ?? 0), $dailySummary);
        $dailyWalkIns = array_map(fn (array $row): int => (int) ($row['walk_ins'] ?? 0), $dailySummary);

        $bookingHours = $this->buildHourlyBookingSeries(clone $bookingBase);
        $transactionHours = $this->buildHourlyTransactionSeries(clone $transactionBase);
        $cashierDailySeries = $this->buildCashierDailySeries(
            $start,
            $end,
            $packageId,
            $cashierId,
            $revenueStatuses,
            array_column($cashierPerformance, 'cashier_id'),
        );

        return [
            [
                'key' => 'cashier_revenue_per_day',
                'label' => 'Pendapatan per Cashier',
                'description' => 'Perbandingan pendapatan cashier per hari dalam periode aktif.',
                'x_axis' => 'Hari',
                'y_axis' => 'Pendapatan',
                'stacked' => true,
                'value_mode' => 'currency',
                'labels' => $cashierDailySeries['labels'] ?? [],
                'datasets' => collect($cashierDailySeries['datasets'] ?? [])
                    ->map(fn (array $dataset, int $index): array => [
                        'label' => (string) ($dataset['cashier_name'] ?? ('Cashier ' . ($index + 1))),
                        'data' => array_map(fn ($value): float => (float) $value, $dataset['data'] ?? []),
                    ])
                    ->values()
                    ->all(),
            ],
            [
                'key' => 'revenue_per_day',
                'label' => 'Revenue per Hari',
                'description' => 'Trend pendapatan harian pada periode report.',
                'x_axis' => 'Hari',
                'y_axis' => 'Pendapatan',
                'stacked' => false,
                'value_mode' => 'currency',
                'labels' => $dailyLabels,
                'datasets' => [[
                    'label' => 'Revenue',
                    'data' => $dailyRevenue,
                ]],
            ],
            [
                'key' => 'bookings_per_day',
                'label' => 'Booking per Hari',
                'description' => 'Jumlah booking yang masuk setiap hari.',
                'x_axis' => 'Hari',
                'y_axis' => 'Jumlah Booking',
                'stacked' => false,
                'value_mode' => 'number',
                'labels' => $dailyLabels,
                'datasets' => [[
                    'label' => 'Bookings',
                    'data' => $dailyBookings,
                ]],
            ],
            [
                'key' => 'transactions_per_day',
                'label' => 'Transaksi per Hari',
                'description' => 'Jumlah transaksi selesai per hari.',
                'x_axis' => 'Hari',
                'y_axis' => 'Jumlah Transaksi',
                'stacked' => false,
                'value_mode' => 'number',
                'labels' => $dailyLabels,
                'datasets' => [[
                    'label' => 'Transactions',
                    'data' => $dailyTransactions,
                ]],
            ],
            [
                'key' => 'walk_ins_per_day',
                'label' => 'Walk-in per Hari',
                'description' => 'Jumlah transaksi walk-in yang terjadi setiap hari.',
                'x_axis' => 'Hari',
                'y_axis' => 'Jumlah Walk-in',
                'stacked' => false,
                'value_mode' => 'number',
                'labels' => $dailyLabels,
                'datasets' => [[
                    'label' => 'Walk-ins',
                    'data' => $dailyWalkIns,
                ]],
            ],
            [
                'key' => 'peak_booking_hours',
                'label' => 'Ramai di Jam Berapa (Booking)',
                'description' => 'Distribusi jam mulai booking paling ramai.',
                'x_axis' => 'Jam',
                'y_axis' => 'Jumlah Booking',
                'stacked' => false,
                'value_mode' => 'number',
                'labels' => $bookingHours['labels'],
                'datasets' => [[
                    'label' => 'Bookings',
                    'data' => $bookingHours['data'],
                ]],
            ],
            [
                'key' => 'peak_transaction_hours',
                'label' => 'Ramai di Jam Berapa (Transaksi)',
                'description' => 'Distribusi jam transaksi paling ramai.',
                'x_axis' => 'Jam',
                'y_axis' => 'Jumlah Transaksi',
                'stacked' => false,
                'value_mode' => 'number',
                'labels' => $transactionHours['labels'],
                'datasets' => [[
                    'label' => 'Transactions',
                    'data' => $transactionHours['data'],
                ]],
            ],
        ];
    }

    protected function buildHourlyBookingSeries(Builder $bookingBase): array
    {
        $rows = $bookingBase->get(['start_at']);

        return $this->buildHourlySeriesFromValues(
            $rows->map(fn (Booking $booking): ?Carbon => $booking->start_at)->all(),
        );
    }

    protected function buildHourlyTransactionSeries(Builder $transactionBase): array
    {
        $rows = $transactionBase->get(['created_at']);

        return $this->buildHourlySeriesFromValues(
            $rows->map(fn (Transaction $transaction): ?Carbon => $transaction->created_at)->all(),
        );
    }

    protected function buildHourlySeriesFromValues(array $values): array
    {
        $hourCounts = array_fill(0, 24, 0);

        foreach ($values as $value) {
            if (! $value instanceof Carbon) {
                continue;
            }

            $hour = (int) $value->format('H');

            if ($hour >= 0 && $hour <= 23) {
                $hourCounts[$hour]++;
            }
        }

        return [
            'labels' => array_map(
                fn (int $hour): string => str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00',
                array_keys($hourCounts),
            ),
            'data' => array_values($hourCounts),
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

    protected function normalizeDateRange(?Carbon $from = null, ?Carbon $to = null): array
    {
        $start = ($from ?? now()->copy()->subDays(6))->copy()->startOfDay();
        $end = ($to ?? now())->copy()->endOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    protected function buildCashierDailySeries(
        Carbon $start,
        Carbon $end,
        ?int $packageId = null,
        ?int $cashierId = null,
        array $revenueStatuses = [],
        array $cashierIds = [],
    ): array {
        $resolvedStatuses = $revenueStatuses !== [] ? $revenueStatuses : $this->revenueTransactionStatuses();

        $selectedCashierIds = collect($cashierIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->take(6)
            ->values();

        if ($selectedCashierIds->isEmpty()) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $cashierRows = User::query()
            ->whereIn('id', $selectedCashierIds->all())
            ->get(['id', 'name'])
            ->keyBy('id');

        $dailyRows = Transaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', $resolvedStatuses)
            ->whereIn('cashier_id', $selectedCashierIds->all())
            ->when($cashierId !== null, function (Builder $query) use ($cashierId): void {
                $query->where('cashier_id', $cashierId);
            })
            ->when($packageId !== null, function (Builder $query) use ($packageId): void {
                $query->whereHas('booking', function (Builder $bookingQuery) use ($packageId): void {
                    $bookingQuery->where('package_id', $packageId);
                });
            })
            ->selectRaw('DATE(created_at) as period, cashier_id, SUM(paid_amount) as total_revenue')
            ->groupBy('period', 'cashier_id')
            ->get();

        $dailyLookup = [];

        foreach ($dailyRows as $row) {
            $cashierRowId = (int) ($row->cashier_id ?? 0);
            $period = (string) ($row->period ?? '');

            if ($cashierRowId <= 0 || $period === '') {
                continue;
            }

            $dailyLookup[$cashierRowId][$period] = (float) ($row->total_revenue ?? 0);
        }

        $labels = [];
        $periodKeys = [];
        $cursor = $start->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $labels[] = $cursor->translatedFormat('j M');
            $periodKeys[] = $cursor->toDateString();
            $cursor->addDay();
        }

        $datasets = $selectedCashierIds
            ->map(function (int $selectedCashierId) use ($cashierRows, $dailyLookup, $periodKeys): array {
                $cashier = $cashierRows->get($selectedCashierId);
                $data = [];
                $totalRevenue = 0.0;

                foreach ($periodKeys as $periodKey) {
                    $value = (float) ($dailyLookup[$selectedCashierId][$periodKey] ?? 0);
                    $data[] = $value;
                    $totalRevenue += $value;
                }

                return [
                    'cashier_id' => $selectedCashierId,
                    'cashier_name' => (string) ($cashier?->name ?? ('Cashier #' . $selectedCashierId)),
                    'data' => $data,
                    'total_revenue' => $totalRevenue,
                    'total_revenue_text' => $this->formatRupiah($totalRevenue),
                ];
            })
            ->values()
            ->all();

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
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
