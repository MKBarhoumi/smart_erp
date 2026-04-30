<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('tenant', function (Request $request) {
            try {
                $tenant = tenancy()->tenant;
                $tenantId = $tenant ? $tenant->id : $request->ip();
                return Limit::perMinute(100)->by($tenantId);
            } catch (\Throwable $e) {
                return Limit::perMinute(100)->by($request->ip());
            }
        });

        RateLimiter::for('critical', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('billing', function (Request $request) {
            try {
                $tenant = tenancy()->tenant;
                $tenantId = $tenant ? $tenant->id : $request->ip();
                return Limit::perMinute(5)->by($tenantId);
            } catch (\Throwable $e) {
                return Limit::perMinute(5)->by($request->ip());
            }
        });
    }
}