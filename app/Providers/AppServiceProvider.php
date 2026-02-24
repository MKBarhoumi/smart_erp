<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use App\Services\TeifXmlParser;
use App\Services\TeifXmlBuilder;
use App\Services\TeifXsdValidator;
use App\Services\InvoiceService;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TeifXmlParser::class);
        $this->app->singleton(TeifXmlBuilder::class);
        $this->app->singleton(TeifXsdValidator::class);
        $this->app->singleton(InvoiceService::class);    }

    public function boot(): void
    {
        //strict mode for eloquent
        Model::shouldBeStrict();

        Gate::define('manage-settings', function ($user) {
            return $user->hasRole('admin');
        });
    }
}
