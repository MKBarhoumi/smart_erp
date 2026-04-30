<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test Domain Resolution ===\n\n";

$host = 'test-tenant2.localhost';
echo "Host: " . $host . "\n";

$resolver = app(\Stancl\Tenancy\Resolvers\DomainTenantResolver::class);

try {
    $tenant = $resolver->resolve($host);

    if ($tenant) {
        echo "✓ Tenant resolved:\n";
        echo "  - ID: " . $tenant->id . "\n";
        echo "  - Name: " . $tenant->name . "\n";
        echo "  - Database: " . $tenant->database_name . "\n";
    } else {
        echo "✗ Tenant not resolved\n";
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}