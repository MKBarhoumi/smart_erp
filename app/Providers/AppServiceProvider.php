<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //strict mode for eloquent
        Model::shouldBeStrict();

        Gate::define('manage-settings', function ($user) {
            return $user->hasRole('admin');
        });
    }
}
