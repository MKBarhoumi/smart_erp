<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Tenant Database Configuration Check ===\n\n";

$tenant = \App\Models\Tenant::latest()->first();
if ($tenant) {
    echo "Tenant found:\n";
    echo "  - ID: " . $tenant->id . "\n";
    echo "  - Name: " . $tenant->name . "\n";
    echo "  - Database name: " . $tenant->database_name . "\n";
    echo "  - Internal db_name: " . ($tenant->getInternal('db_name') ?? 'NOT SET') . "\n";
    echo "  - Database config name: " . $tenant->database()->getName() . "\n";
    echo "  - Database config connection:\n";
    var_dump($tenant->database()->connection());
} else {
    echo "No tenant found\n";
}