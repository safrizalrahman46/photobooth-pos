<?php

namespace App\Filament\Pages;

use App\Services\AdminDashboardDataService;
use App\Services\AdminQueuePageService;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class AdminDashboard extends Page
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

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

    public function getHeading(): string | Htmlable | null
    {
        return null;
    }

    public function bootstrapData(): array
    {
        /** @var AdminDashboardDataService $service */
        $service = app(AdminDashboardDataService::class);
        /** @var AdminQueuePageService $queuePageService */
        $queuePageService = app(AdminQueuePageService::class);
        $paginator = $service->paginatedRows('', 'all', 15);

        return [
            'initialStats' => $service->stats(),
            'summaryCards' => $service->summaryCards(),
            'revenueOverview' => $service->revenueOverview(),
            'ownerHighlights' => $service->ownerHighlights(),
            'ownerModules' => $service->ownerModules(),
            'queueLive' => $queuePageService->live(),
            'queueBookingOptions' => $queuePageService->bookingOptions(),
            'recentTransactions' => $service->recentTransactions(),
            'recentActivities' => $service->recentActivities(),
            'queueSnapshot' => $service->queueSnapshot(),
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
