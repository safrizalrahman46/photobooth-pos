<?php

namespace App\Filament\Pages;

use App\Services\ActivityLogger;
use App\Services\AdminDashboardDataService;
use App\Services\AdminQueuePageService;
use App\Services\BookingReadService;
use App\Services\ReportService;
use App\Services\TransactionReadService;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class AdminDashboard extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected string $view = 'filament.pages.admin-dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->redirectRoute('admin.dashboard');
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function bootstrapData(): array
    {
        /** @var AdminDashboardDataService $service */
        $service = app(AdminDashboardDataService::class);
        /** @var AdminQueuePageService $queuePageService */
        $queuePageService = app(AdminQueuePageService::class);
        /** @var BookingReadService $bookingReadService */
        $bookingReadService = app(BookingReadService::class);
        /** @var ReportService $reportService */
        $reportService = app(ReportService::class);
        /** @var TransactionReadService $transactionReadService */
        $transactionReadService = app(TransactionReadService::class);
        /** @var ActivityLogger $activityLogger */
        $activityLogger = app(ActivityLogger::class);
        $paginator = $bookingReadService->paginatedRows('', 'all', 15);

        return [
            'initialStats' => $reportService->stats(),
            'summaryCards' => $reportService->summaryCards(),
            'revenueOverview' => $reportService->revenueOverview(),
            'ownerHighlights' => $reportService->ownerHighlights(),
            'ownerModules' => $service->ownerModules(),
            'queueLive' => $queuePageService->live(),
            'recentTransactions' => $transactionReadService->recentDetailed(),
            'recentActivities' => $activityLogger->recentRows(),
            'queueSnapshot' => $queuePageService->snapshot(),
            'initialRows' => $paginator->items(),
            'initialPagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'dataUrl' => route('admin.dashboard.data'),
        ];
    }
}
