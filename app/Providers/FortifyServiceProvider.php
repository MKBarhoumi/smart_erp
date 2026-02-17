<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Intentionally empty — provided as a minimal stub for autoload/migrations
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // No-op for test/dev environment
    }
}
