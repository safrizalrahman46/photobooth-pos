<?php

namespace App\Providers;

use App\Services\AppSettingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('walk-in', fn (Request $request) => Limit::perMinute(8)->by($request->ip()));

        View::share('siteSettings', app(AppSettingService::class)->publicSettings());
    }
}
