<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Tenancy;

abstract class TenantSeeder extends Seeder
{
    /**
     * Run the seeder for a specific tenant.
     *
     * @param Tenant $tenant
     * @return void
     */
    public function runForTenant(Tenant $tenant): void
    {
        tenancy()->initialize($tenant);

        try {
            $this->run();
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Run the seeder for all tenants.
     *
     * @return void
     */
    public function runForAllTenants(): void
    {
        Tenant::all()->each(function ($tenant) {
            $this->runForTenant($tenant);
        });
    }
}