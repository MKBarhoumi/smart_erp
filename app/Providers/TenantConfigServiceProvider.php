<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Features\TenantConfig;

class TenantConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        TenantConfig::$storageToConfigMap = [
            'app_name' => 'app.name',
            'app_locale' => 'app.locale',
            'app_timezone' => 'app.timezone',
            'mail_from_address' => 'mail.from.address',
            'mail_from_name' => 'mail.from.name',
        ];
    }
}