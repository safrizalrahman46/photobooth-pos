<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockFilamentRoutesWhenDisabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('admin_ui.block_filament_routes', true)) {
            return $next($request);
        }

        if (config('admin_ui.driver', 'vue') !== 'vue') {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');

        if ($path === 'filament' || str_starts_with($path, 'filament/')) {
            abort(404);
        }

        return $next($request);
    }
}

