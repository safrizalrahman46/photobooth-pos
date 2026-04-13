<?php

namespace App\Filament\Pages;

use App\Services\AdminDashboardDataService;
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

    public function getHeading(): string | Htmlable | null
    {
        return null;
    }

    public function bootstrapData(): array
    {
        /** @var AdminDashboardDataService $service */
        $service = app(AdminDashboardDataService::class);
        $paginator = $service->paginatedRows('', 'all', 15);

        return [
            'initialStats' => $service->stats(),
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
