<?php

namespace App\Providers;

use App\Providers\Filament\AdminPanelProvider;
use App\Services\AppSettingService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config('admin_ui.driver', 'vue') === 'filament' && class_exists(AdminPanelProvider::class)) {
            $this->app->register(AdminPanelProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::share('siteSettings', app(AppSettingService::class)->publicSettings());
    }
}
