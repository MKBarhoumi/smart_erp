<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Tenancy;

/**
 * Tenant Provisioning Service
 *
 * This service handles the creation and provisioning of tenant databases.
 *
 * @package App\Services
 */
class TenantProvisioningService
{
    /**
     * Provision a tenant with database and migrations.
     *
     * @param Tenant $tenant
     * @return void
     */
    public function provision(Tenant $tenant): void
    {
        $this->createDatabase($tenant);
        $this->runMigrations($tenant);
        $this->seedDatabase($tenant);
    }

    /**
     * Create a new tenant with database.
     *
     * @param array $data
     * @return Tenant
     */
    public function createTenant(array $data): Tenant
    {
        // Generate database name if not provided
        if (!isset($data['database_name'])) {
            $data['database_name'] = 'tenant_' . str_replace('-', '', $data['id']);
        }

        // Create tenant record
        $tenant = Tenant::create($data);

        // Provision tenant
        $this->provision($tenant);

        return $tenant;
    }

    /**
     * Create tenant database.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function createDatabase(Tenant $tenant): void
    {
        // Get the database manager for the tenant
        $manager = $tenant->database()->manager();

        // Create the database
        $manager->createDatabase($tenant);
    }

    /**
     * Run tenant migrations.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function runMigrations(Tenant $tenant): void
    {
        // Run migrations for the tenant
        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->id],
        ]);
    }

    /**
     * Seed tenant database.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function seedDatabase(Tenant $tenant): void
    {
        tenancy()->initialize($tenant);

        try {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@' . $tenant->domains->first()->domain,
                'password' => Hash::make('password'),
            ]);
        } finally {
            tenancy()->end();
        }

        Artisan::call('tenants:seed', [
            '--tenants' => [$tenant->id],
        ]);
    }

    /**
     * Create domain for tenant.
     *
     * @param Tenant $tenant
     * @param string $domain
     * @param bool $isPrimary
     * @return Domain
     */
    public function createDomain(Tenant $tenant, string $domain, bool $isPrimary = false): Domain
    {
        return Domain::create([
            'domain' => $domain,
            'tenant_id' => $tenant->id,
            'is_primary' => $isPrimary,
        ]);
    }

    /**
     * Delete tenant and database.
     *
     * @param Tenant $tenant
     * @return void
     */
    public function deleteTenant(Tenant $tenant): void
    {
        // Get the database manager for the tenant
        $manager = $tenant->database()->manager();

        // Delete the database
        $manager->deleteDatabase($tenant);

        // Delete the tenant record (this will cascade delete domains)
        $tenant->delete();
    }

    /**
     * Check if tenant database exists.
     *
     * @param Tenant $tenant
     * @return bool
     */
    public function databaseExists(Tenant $tenant): bool
    {
        // Get the database manager for the tenant
        $manager = $tenant->database()->manager();

        // Check if database exists
        return $manager->databaseExists($tenant->database()->getName());
    }
}
