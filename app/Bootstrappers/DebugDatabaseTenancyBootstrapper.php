<?php

namespace App\Bootstrappers;

use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper as BaseDatabaseTenancyBootstrapper;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\DatabaseManager;
use Stancl\Tenancy\Exceptions\TenantDatabaseDoesNotExistException;

class DebugDatabaseTenancyBootstrapper implements TenancyBootstrapper
{
    /** @var DatabaseManager */
    protected $database;

    public function __construct(DatabaseManager $database)
    {
        $this->database = $database;
    }

    public function bootstrap(Tenant $tenant)
    {
        echo "  [DEBUG] DatabaseTenancyBootstrapper::bootstrap() called\n";
        echo "  [DEBUG] Tenant ID: " . $tenant->getTenantKey() . "\n";
        echo "  [DEBUG] Tenant database name: " . $tenant->database()->getName() . "\n";
        echo "  [DEBUG] Tenant database connection array:\n";
        var_dump($tenant->database()->connection());

        /** @var TenantWithDatabase $tenant */

        // Better debugging, but breaks cached lookup in prod
        if (app()->environment('local')) {
            $database = $tenant->database()->getName();
            if (! $tenant->database()->manager()->databaseExists($database)) {
                throw new TenantDatabaseDoesNotExistException($database);
            }
        }

        echo "  [DEBUG] Calling connectToTenant()\n";
        $this->database->connectToTenant($tenant);
        echo "  [DEBUG] connectToTenant() completed\n";

        echo "  [DEBUG] Tenant connection config after connectToTenant():\n";
        var_dump(config('database.connections.tenant'));

        echo "  [DEBUG] Default connection after connectToTenant(): " . config('database.default') . "\n";
    }

    public function revert()
    {
        echo "  [DEBUG] DatabaseTenancyBootstrapper::revert() called\n";
        $this->database->reconnectToCentral();
        echo "  [DEBUG] reconnectToCentral() completed\n";
    }
}