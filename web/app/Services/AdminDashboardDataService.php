<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\AddOn;
use App\Models\Branch;
use App\Models\DesignCatalog;
use App\Models\Package;

class AdminDashboardDataService
{
    public function __construct(
        private readonly AdminQueuePageService $adminQueuePageService,
        private readonly BookingReadService $bookingReadService,
        private readonly ReportService $reportService,
        private readonly TransactionReadService $transactionReadService,
        private readonly ActivityLogger $activityLogger,
        private readonly InventoryService $inventoryService,
        private readonly AdminPackageService $adminPackageService,
        private readonly AdminAddOnService $adminAddOnService,
        private readonly AdminDesignService $adminDesignService,
        private readonly AdminUserService $adminUserService,
        private readonly AdminBranchService $adminBranchService,
        private readonly AdminTimeSlotService $adminTimeSlotService,
        private readonly AdminBlackoutDateService $adminBlackoutDateService,
        private readonly AdminPrinterSettingService $adminPrinterSettingService,
        private readonly AdminPaymentService $adminPaymentService,
        private readonly AdminReferralService $adminReferralService,
    ) {}

    public function bootstrapPayload(
        string $search = '',
        string $status = 'all',
        int $perPage = 15,
        string $sortBy = 'date_time',
        string $sortDir = 'desc',
    ): array {
        return array_merge(
            $this->snapshot(),
            $this->bookingReadService->rowsPayload($search, $status, $perPage, $sortBy, $sortDir),
        );
    }

    public function snapshot(): array
    {
        return [
            'initialStats' => $this->reportService->stats(),
            'summaryCards' => $this->reportService->summaryCards(),
            'revenueOverview' => $this->reportService->revenueOverview(),
            'ownerHighlights' => $this->reportService->ownerHighlights(),
            'ownerModules' => $this->ownerModules(),
            'queueLive' => $this->adminQueuePageService->live(),
            'queueBookingOptions' => $this->adminQueuePageService->bookingOptions(),
            'recentTransactions' => $this->transactionReadService->recentDetailed(),
            'recentActivities' => $this->activityLogger->recentRows(),
            'queueSnapshot' => $this->adminQueuePageService->snapshot(),
            'initialPackages' => $this->adminPackageService->managementRows(),
            'initialAddOns' => $this->adminAddOnService->managementRows(),
            'initialDesigns' => $this->adminDesignService->managementRows(),
            'initialUsers' => $this->adminUserService->rows(),
            'initialUserRoles' => $this->adminUserService->roleOptions(),
            'initialBookingOptions' => $this->bookingFormOptions(),
            'initialBranches' => $this->adminBranchService->rows(),
            'initialTimeSlots' => $this->adminTimeSlotService->rows(),
            'initialBlackoutDates' => $this->adminBlackoutDateService->rows(),
            'initialPrinterSettings' => $this->adminPrinterSettingService->rows(['include_inactive' => true]),
            'initialPayments' => $this->adminPaymentService->rows(),
            'initialPaymentTransactionOptions' => $this->adminPaymentService->transactionOptions(),
            'initialReferralPayload' => $this->adminReferralService->payload(),
            'initialInventoryItems' => $this->inventoryService->itemRows(),
            'initialInventoryMovements' => $this->inventoryService->movementRows(),
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
                ->with('inventoryItems:id,code,name,unit,available_stock,low_stock_threshold,is_active')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'package_id', 'code', 'name', 'price', 'max_qty', 'is_physical'])
                ->map(function (AddOn $addOn): array {
                    $price = (float) $addOn->price;
                    $inventoryItems = $this->inventoryService->mapAddOnInventoryItems($addOn);
                    $effectiveStock = $this->inventoryService->effectiveAvailableStock($inventoryItems);
                    $stockTone = $this->inventoryService->effectiveStockTone($inventoryItems, $effectiveStock);

                    return [
                        'id' => (int) $addOn->id,
                        'package_id' => $addOn->package_id ? (int) $addOn->package_id : null,
                        'code' => (string) $addOn->code,
                        'name' => (string) $addOn->name,
                        'price' => $price,
                        'max_qty' => max(1, (int) $addOn->max_qty),
                        'is_physical' => (bool) $addOn->is_physical,
                        'inventory_items' => $inventoryItems,
                        'effective_available_stock' => $effectiveStock,
                        'effective_stock_status' => $stockTone['status'],
                        'effective_stock_label' => $stockTone['label'],
                        'price_text' => $this->formatRupiah($price),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function ownerModules(): array
    {
        return [
            [
                'label' => 'Kelola Booking',
                'description' => 'Monitoring booking, ubah status, dan cek detail pelanggan.',
                'url' => url('/admin/bookings'),
                'badge' => number_format(\App\Models\Booking::query()->count()),
                'tone' => 'blue',
                'icon' => 'calendar',
            ],
            [
                'label' => 'Transaksi',
                'description' => 'Pantau pembayaran dan performa transaksi harian.',
                'url' => url('/admin/transactions'),
                'badge' => number_format(\App\Models\Transaction::query()->count()),
                'tone' => 'emerald',
                'icon' => 'receipt',
            ],
            [
                'label' => 'Antrian Studio',
                'description' => 'Pantau antrean pelanggan aktif secara real-time.',
                'url' => url('/admin/queue'),
                'badge' => number_format(\App\Models\QueueTicket::query()->count()),
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
                'badge' => number_format(\App\Models\User::query()->count()),
                'tone' => 'slate',
                'icon' => 'users',
            ],
        ];
    }

    private function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
