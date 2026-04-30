<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Check Bootstrapper Calling ===\n\n";

// Get the bootstrappers
echo "Bootstrappers:\n";
$bootstrapperClasses = config('tenancy.bootstrappers');
foreach ($bootstrapperClasses as $bootstrapperClass) {
    echo "  - " . $bootstrapperClass . "\n";
}

// Initialize tenant
$tenant = \App\Models\Tenant::where('data->database_name', 'tenant_test_tenant')->first();

if ($tenant) {
    echo "\nInitializing tenant...\n";
    tenancy()->initialize($tenant);

    echo "\nAfter initialization:\n";
    echo "  - Tenancy initialized: " . (tenancy()->initialized ? 'YES' : 'NO') . "\n";
    echo "  - Current tenant: " . (tenancy()->tenant ? tenancy()->tenant->name : 'NULL') . "\n";
    echo "  - DB connection: " . DB::getDefaultConnection() . "\n";
    echo "  - DB name: " . DB::connection()->getDatabaseName() . "\n";

    // Check tenant connection config
    echo "\nTenant connection config:\n";
    var_dump(config('database.connections.tenant'));

    tenancy()->end();
} else {
    echo "Tenant not found\n";
}