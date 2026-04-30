<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Check Existing Domains ===\n\n";

$domains = \App\Models\Domain::all();

echo "Existing domains:\n";
foreach ($domains as $domain) {
    echo "  - " . $domain->domain . " (Tenant: " . $domain->tenant_id . ")\n";
}

echo "\nTotal domains: " . $domains->count() . "\n";