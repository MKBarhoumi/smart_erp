<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Check Domain Resolution ===\n\n";

$domain = \App\Models\Domain::where('domain', 'test-tenant2.localhost')->first();

if ($domain) {
    echo "✓ Domain found:\n";
    echo "  - Domain: " . $domain->domain . "\n";
    echo "  - Tenant ID: " . $domain->tenant_id . "\n";
    echo "  - Is Primary: " . ($domain->is_primary ? 'YES' : 'NO') . "\n";

    $tenant = $domain->tenant;
    if ($tenant) {
        echo "\n✓ Tenant found:\n";
        echo "  - ID: " . $tenant->id . "\n";
        echo "  - Name: " . $tenant->name . "\n";
        echo "  - Database: " . $tenant->database_name . "\n";
        echo "  - Internal db_name: " . ($tenant->getInternal('db_name') ?? 'NOT SET') . "\n";
    } else {
        echo "\n✗ Tenant not found\n";
    }
} else {
    echo "✗ Domain not found\n";
}