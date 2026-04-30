<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use App\Services\TeifXmlParser;
use App\Services\TeifXmlBuilder;
use App\Services\TeifXsdValidator;
use App\Services\InvoiceService;
use Stancl\Tenancy\Events\TenancyInitialized;
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

        // Register tenancy bootstrapper event listener
        Event::listen(TenancyInitialized::class, function ($event) {
            foreach ($event->tenancy->getBootstrappers() as $bootstrapper) {
                $bootstrapper->bootstrap($event->tenancy->tenant);
            }
        });
    }
}
