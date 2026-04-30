<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Step 5: Test Database Connection Switching ===\n\n";

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

// Initialize tenant
echo "\nInitializing tenant...\n";
tenancy()->initialize($tenant);
echo "✓ Tenant initialized\n";

// Check default connection
echo "\nDefault connection: " . DB::getDefaultConnection() . "\n";

// Check tenant connection config
echo "\nTenant connection config:\n";
var_dump(config('database.connections.tenant'));

// Check event listeners
echo "\nEvent listeners for TenancyInitialized:\n";
$listeners = app('events')->getListeners(\Stancl\Tenancy\Events\TenancyInitialized::class);
var_dump($listeners);

// End tenancy
tenancy()->end();
echo "\n✓ Tenancy ended\n";