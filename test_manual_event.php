<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Manual Event Test ===\n\n";

// Get a tenant
$tenant = \App\Models\Tenant::latest()->first();
if (!$tenant) {
    echo "No tenant found. Creating one...\n";
    $tenant = \App\Models\Tenant::create([
        'id' => \Illuminate\Support\Str::uuid(),
        'name' => 'Test Company',
        'slug' => 'test-company',
        'matricule_fiscal' => '1234567890',
        'database_name' => 'tenant_test_company',
        'status' => 'trial',
        'trial_ends_at' => now()->addDays(14),
    ]);
    $tenant->setInternal('db_name', $tenant->database_name);
    $tenant->save();
}

echo "Tenant found:\n";
echo "  - ID: " . $tenant->id . "\n";
echo "  - Name: " . $tenant->name . "\n";
echo "  - Database: " . $tenant->database_name . "\n";

// Check default connection before
echo "\nDefault connection before: " . DB::getDefaultConnection() . "\n";

// Initialize tenant
echo "\nInitializing tenant...\n";
tenancy()->initialize($tenant);
echo "✓ Tenant initialized\n";

// Check default connection after
echo "\nDefault connection after: " . DB::getDefaultConnection() . "\n";

// Check tenant connection config
echo "\nTenant connection config:\n";
var_dump(config('database.connections.tenant'));

// End tenancy
tenancy()->end();
echo "\n✓ Tenancy ended\n";

// Check default connection after end
echo "\nDefault connection after end: " . DB::getDefaultConnection() . "\n";

// Now try manual event firing
echo "\n=== Manual Event Firing Test ===\n\n";

// Initialize tenant again
echo "Initializing tenant again...\n";
tenancy()->initialize($tenant);
echo "✓ Tenant initialized\n";

// Check default connection before manual event
echo "\nDefault connection before manual event: " . DB::getDefaultConnection() . "\n";

// Manually fire the TenancyInitialized event
echo "\nManually firing TenancyInitialized event...\n";
event(new \Stancl\Tenancy\Events\TenancyInitialized(tenancy()));
echo "✓ Event fired\n";

// Check default connection after manual event
echo "\nDefault connection after manual event: " . DB::getDefaultConnection() . "\n";

// Check tenant connection config
echo "\nTenant connection config after manual event:\n";
var_dump(config('database.connections.tenant'));

// End tenancy
tenancy()->end();
echo "\n✓ Tenancy ended\n";