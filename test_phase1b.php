<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Phase 1B Manual Test Flow ===\n\n";

// Step 1: Create a tenant
echo "Step 1: Creating tenant...\n";
try {
    $provisioningService = app(\App\Services\TenantProvisioningService::class);

    $tenantData = [
        'id' => \Illuminate\Support\Str::uuid(),
        'name' => 'Test Company',
        'slug' => 'test-company',
        'matricule_fiscal' => '1234567890',
        'database_name' => 'tenant_test_company',
        'status' => 'trial',
        'trial_ends_at' => now()->addDays(14),
    ];

    $tenant = $provisioningService->createTenant($tenantData);

    echo "✓ Tenant created successfully\n";
    echo "  - ID: " . $tenant->id . "\n";
    echo "  - Name: " . $tenant->name . "\n";
    echo "  - Slug: " . $tenant->slug . "\n";
    echo "  - Database: " . $tenant->database_name . "\n";
    echo "  - Status: " . $tenant->status . "\n";
} catch (\Exception $e) {
    echo "✗ Error creating tenant: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Step 2: Create a domain for the tenant
echo "\nStep 2: Creating domain...\n";
try {
    $domain = $provisioningService->createDomain($tenant, 'test-company.localhost', true);

    echo "✓ Domain created successfully\n";
    echo "  - ID: " . $domain->id . "\n";
    echo "  - Domain: " . $domain->domain . "\n";
    echo "  - Tenant ID: " . $domain->tenant_id . "\n";
    echo "  - Is Primary: " . ($domain->is_primary ? 'YES' : 'NO') . "\n";
} catch (\Exception $e) {
    echo "✗ Error creating domain: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Step 3: Verify tenant database exists
echo "\nStep 3: Verifying tenant database...\n";
try {
    $databaseExists = $provisioningService->databaseExists($tenant);
    echo "✓ Database exists: " . ($databaseExists ? 'YES' : 'NO') . "\n";
} catch (\Exception $e) {
    echo "✗ Error checking database: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Step 4: Test tenant initialization
echo "\nStep 4: Testing tenant initialization...\n";
try {
    $domain = \App\Models\Domain::where('domain', 'test-company.localhost')->first();
    if ($domain) {
        tenancy()->initialize($domain->tenant);
        echo "✓ Tenant initialized successfully\n";
        echo "  - Current tenant: " . tenancy()->tenant->name . "\n";
        echo "  - Tenancy initialized: " . (tenancy()->initialized ? 'YES' : 'NO') . "\n";

        // Test database connection
        $connection = DB::connection();
        echo "  - Database connection: " . $connection->getDatabaseName() . "\n";

        // Test if we can query tenant database
        $userCount = DB::table('users')->count();
        echo "  - Users in tenant DB: " . $userCount . "\n";

        // End tenancy
        tenancy()->end();
        echo "✓ Tenancy ended successfully\n";
    } else {
        echo "✗ Domain not found\n";
    }
} catch (\Exception $e) {
    echo "✗ Error initializing tenant: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Step 5: Test domain resolution
echo "\nStep 5: Testing domain resolution...\n";
try {
    $resolver = app(\Stancl\Tenancy\Resolvers\DomainTenantResolver::class);
    $domain = $resolver->findByDomain('test-company.localhost');

    if ($domain) {
        echo "✓ Domain resolved successfully\n";
        echo "  - Domain: " . $domain->domain . "\n";
        echo "  - Tenant: " . $domain->tenant->name . "\n";
    } else {
        echo "✗ Domain not found\n";
    }
} catch (\Exception $e) {
    echo "✗ Error resolving domain: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Step 6: Verify tenant tables
echo "\nStep 6: Verifying tenant tables...\n";
try {
    tenancy()->initialize($domain->tenant);

    $tables = DB::select("SHOW TABLES");
    echo "✓ Tenant tables found: " . count($tables) . "\n";
    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        echo "  - " . $tableName . "\n";
    }

    tenancy()->end();
} catch (\Exception $e) {
    echo "✗ Error verifying tables: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== All Tests Complete ===\n";
echo "\nNext Steps:\n";
echo "1. Access http://test-company.localhost in your browser\n";
echo "2. You should see the application using the tenant database\n";
echo "3. The tenant should be automatically identified from the domain\n";
echo "4. All data should be isolated to this tenant\n";
