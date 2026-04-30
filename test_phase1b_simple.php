<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Phase 1B Simplified Test ===\n\n";

// Step 1: Create a tenant
echo "Step 1: Creating tenant...\n";
try {
    $tenant = \App\Models\Tenant::create([
        'id' => \Illuminate\Support\Str::uuid(),
        'name' => 'Test Company',
        'slug' => 'test-company',
        'matricule_fiscal' => '1234567890',
        'database_name' => 'tenant_test_company',
        'status' => 'trial',
        'trial_ends_at' => now()->addDays(14),
    ]);

    echo "✓ Tenant created successfully\n";
    echo "  - ID: " . $tenant->id . "\n";
    echo "  - Name: " . $tenant->name . "\n";
    echo "  - Database: " . $tenant->database_name . "\n";

    // Set the internal db_name to match the database_name field
    $tenant->setInternal('db_name', $tenant->database_name);
    $tenant->save();
} catch (\Exception $e) {
    echo "✗ Error creating tenant: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Create tenant database manually
echo "\nStep 2: Creating tenant database...\n";
try {
    $manager = $tenant->database()->manager();
    $manager->setConnection('mysql'); // Use the MySQL connection to create the database

    if ($manager->databaseExists($tenant->database_name)) {
        echo "✓ Database already exists\n";
    } else {
        $result = $manager->createDatabase($tenant);
        echo "✓ Database created: " . ($result ? 'YES' : 'NO') . "\n";
    }
} catch (\Exception $e) {
    echo "✗ Error creating database: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 3: Create domain
echo "\nStep 3: Creating domain...\n";
try {
    $domainName = 'test-company-' . time() . '.localhost';
    $domain = \App\Models\Domain::create([
        'id' => \Illuminate\Support\Str::uuid(),
        'domain' => $domainName,
        'tenant_id' => $tenant->id,
        'is_primary' => true,
    ]);

    echo "✓ Domain created successfully\n";
    echo "  - Domain: " . $domain->domain . "\n";
} catch (\Exception $e) {
    echo "✗ Error creating domain: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 4: Test tenant initialization
echo "\nStep 4: Testing tenant initialization...\n";
try {
    // Check tenant connection configuration before initialization
    echo "  - Checking tenant connection configuration before initialization...\n";
    $tenantConfigBefore = config('database.connections.tenant');
    echo "  - Tenant config database before: " . ($tenantConfigBefore['database'] ?? 'NOT SET') . "\n";

    // Check tenant database config
    echo "  - Checking tenant database config...\n";
    $tenantDatabaseConfig = config('tenancy.database');
    echo "  - Central connection: " . ($tenantDatabaseConfig['central_connection'] ?? 'NOT SET') . "\n";

    // Check bootstrappers
    echo "  - Checking bootstrappers...\n";
    $bootstrappers = config('tenancy.bootstrappers');
    echo "  - Bootstrappers: " . (is_array($bootstrappers) ? count($bootstrappers) . ' bootstrappers' : 'NOT SET') . "\n";
    if (is_array($bootstrappers)) {
        foreach ($bootstrappers as $bootstrapper) {
            echo "  - Bootstrapper: " . $bootstrapper . "\n";
        }
    }

    // Check bootstrap configuration
    echo "  - Checking bootstrap configuration...\n";
    $bootstrap = config('tenancy.bootstrap');
    echo "  - Bootstrap: " . (is_array($bootstrap) ? 'array' : 'NOT SET') . "\n";
    if (is_array($bootstrap)) {
        foreach ($bootstrap as $key => $value) {
            echo "  - Bootstrap {$key}: " . ($value ? 'true' : 'false') . "\n";
        }
    }

    // Check tenant database name
    echo "  - Checking tenant database name...\n";
    echo "  - Tenant database name: " . $tenant->database_name . "\n";
    echo "  - Tenant database config name: " . $tenant->database()->getName() . "\n";
    echo "  - Tenant database config connection array:\n";
    var_dump($tenant->database()->connection());

    // Check tenant database config connection
    echo "  - Checking tenant database config connection...\n";
    $tenantDatabaseConfigConnection = config('tenancy.database.template_tenant_connection');
    echo "  - Template tenant connection: " . ($tenantDatabaseConfigConnection ?? 'NOT SET') . "\n";

    tenancy()->initialize($tenant);
    echo "✓ Tenant initialized successfully\n";
    echo "  - Current tenant: " . tenancy()->tenant->name . "\n";
    echo "  - Tenancy initialized: " . (tenancy()->initialized ? 'YES' : 'NO') . "\n";

    // Check if the tenant connection was created
    echo "\n  - Checking if tenant connection was created...\n";
    $tenantConnectionConfig = config('database.connections.tenant');
    echo "  - Tenant connection config database: " . ($tenantConnectionConfig['database'] ?? 'NOT SET') . "\n";

    // Check tenant connection configuration after initialization
    echo "\n  - Checking tenant connection configuration after initialization...\n";
    $tenantConfigAfter = config('database.connections.tenant');
    echo "  - Tenant config database after: " . ($tenantConfigAfter['database'] ?? 'NOT SET') . "\n";
    echo "  - Tenant config host: " . ($tenantConfigAfter['host'] ?? 'NOT SET') . "\n";
    echo "  - Tenant config full array:\n";
    var_dump($tenantConfigAfter);

    // Check default connection
    echo "\n  - Checking default connection...\n";
    echo "  - Default connection: " . config('database.default') . "\n";

    // Check database connection
    $connection = DB::connection();
    echo "\n  - Database connection: " . $connection->getDatabaseName() . "\n";
    echo "  - Connection name: " . $connection->getName() . "\n";

    // Check if we're in the tenant database
    if ($connection->getDatabaseName() === $tenant->database_name) {
        echo "✓ Connected to tenant database\n";
    } else {
        echo "✗ Not connected to tenant database\n";
        echo "  - Expected: " . $tenant->database_name . "\n";
        echo "  - Actual: " . $connection->getDatabaseName() . "\n";
    }

    // Try to manually switch to tenant connection
    echo "\n  - Testing manual connection switch...\n";
    DB::purge('tenant');
    $tenantConnection = DB::connection('tenant');
    echo "  - Tenant connection database: " . $tenantConnection->getDatabaseName() . "\n";

    // Try to manually set the tenant connection database
    echo "\n  - Testing manual database name setting...\n";
    config(['database.connections.tenant.database' => $tenant->database_name]);
    DB::purge('tenant');
    $tenantConnection2 = DB::connection('tenant');
    echo "  - Tenant connection database after manual set: " . $tenantConnection2->getDatabaseName() . "\n";

    // Try to manually set the default connection
    echo "\n  - Testing manual default connection setting...\n";
    config(['database.default' => 'tenant']);
    $connection3 = DB::connection();
    echo "  - Default connection database after manual set: " . $connection3->getDatabaseName() . "\n";

    tenancy()->end();
    echo "✓ Tenancy ended successfully\n";
} catch (\Exception $e) {
    echo "✗ Error initializing tenant: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";
