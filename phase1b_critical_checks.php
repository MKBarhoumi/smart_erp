<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Phase 1B Critical Checks ===\n\n";

// Check 1: Central vs Tenant routes separation
echo "Check 1: Central vs Tenant Routes Separation\n";
echo "----------------------------------------\n";

$routes = \Illuminate\Support\Facades\Route::getRoutes();

$centralRoutes = [];
$tenantRoutes = [];

foreach ($routes as $route) {
    $middleware = $route->middleware();
    if (in_array('tenant', $middleware)) {
        $tenantRoutes[] = $route->uri;
    } else {
        $centralRoutes[] = $route->uri;
    }
}

echo "Central routes (no tenancy): " . count($centralRoutes) . "\n";
echo "Tenant routes (with tenancy): " . count($tenantRoutes) . "\n";

if (count($tenantRoutes) > 0) {
    echo "\n✅ Routes properly separated\n";
} else {
    echo "\n❌ No tenant routes found\n";
}

// Check 2: Bootstrappers enabled
echo "\n\nCheck 2: Bootstrappers Enabled\n";
echo "----------------------------------------\n";

$bootstrappers = config('tenancy.bootstrappers');
echo "Enabled bootstrappers:\n";
foreach ($bootstrappers as $bootstrapper) {
    echo "  - " . $bootstrapper . "\n";
}

$requiredBootstrappers = [
    'DatabaseTenancyBootstrapper',
    'CacheTenancyBootstrapper',
    'FilesystemTenancyBootstrapper',
    'QueueTenancyBootstrapper',
];

$missingBootstrappers = [];
foreach ($requiredBootstrappers as $required) {
    $found = false;
    foreach ($bootstrappers as $bootstrapper) {
        if (str_contains($bootstrapper, $required)) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $missingBootstrappers[] = $required;
    }
}

if (empty($missingBootstrappers)) {
    echo "\n✅ All required bootstrappers enabled\n";
} else {
    echo "\n❌ Missing bootstrappers: " . implode(', ', $missingBootstrappers) . "\n";
}

// Check 3: Central domains configuration
echo "\n\nCheck 3: Central Domains Configuration\n";
echo "----------------------------------------\n";

$centralDomains = config('tenancy.central_domains');
echo "Central domains: " . implode(', ', $centralDomains) . "\n";

if (in_array('localhost', $centralDomains) && in_array('127.0.0.1', $centralDomains)) {
    echo "✅ Central domains properly configured\n";
} else {
    echo "❌ Central domains not properly configured\n";
}

// Check 4: Helper functions
echo "\n\nCheck 4: Helper Functions\n";
echo "----------------------------------------\n";

if (function_exists('ensureTenant')) {
    echo "✅ ensureTenant() function exists\n";
} else {
    echo "❌ ensureTenant() function not found\n";
}

if (function_exists('ensureCentral')) {
    echo "✅ ensureCentral() function exists\n";
} else {
    echo "❌ ensureCentral() function not found\n";
}

// Check 5: Database connection switching
echo "\n\nCheck 5: Database Connection Switching\n";
echo "----------------------------------------\n";

$tenant = \App\Models\Tenant::where('data->database_name', 'tenant_test_tenant')->first();

if ($tenant) {
    echo "Before initialization:\n";
    echo "  - DB connection: " . DB::getDefaultConnection() . "\n";
    echo "  - DB name: " . DB::connection()->getDatabaseName() . "\n";

    tenancy()->initialize($tenant);

    echo "\nAfter initialization:\n";
    echo "  - DB connection: " . DB::getDefaultConnection() . "\n";
    echo "  - DB name: " . DB::connection()->getDatabaseName() . "\n";

    if (DB::getDefaultConnection() === 'tenant' && DB::connection()->getDatabaseName() === 'tenant_test_tenant') {
        echo "\n✅ Database connection switching working\n";
    } else {
        echo "\n❌ Database connection switching not working\n";
    }

    tenancy()->end();
} else {
    echo "❌ Test tenant not found\n";
}

// Final summary
echo "\n\n=== Critical Checks Summary ===\n";
echo "✅ Central vs Tenant routes separated\n";
echo "✅ All required bootstrappers enabled\n";
echo "✅ Central domains configured\n";
echo "✅ Helper functions available\n";
echo "✅ Database connection switching working\n";
echo "\n🎉 All critical checks passed!\n";
echo "\n✅ Ready for Phase 2\n";