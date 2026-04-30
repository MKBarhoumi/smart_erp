<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Create Tenant with Domain ===\n\n";

// Create a tenant
$tenant = \App\Models\Tenant::create([
    'id' => \Illuminate\Support\Str::uuid(),
    'name' => 'Test Tenant',
    'slug' => 'test-tenant',
    'matricule_fiscal' => '1234567890',
    'database_name' => 'tenant_test_tenant',
    'status' => 'trial',
    'trial_ends_at' => now()->addDays(14),
]);

// Set the internal db_name
$tenant->setInternal('db_name', $tenant->database_name);
$tenant->save();

echo "✓ Tenant created:\n";
echo "  - ID: " . $tenant->id . "\n";
echo "  - Name: " . $tenant->name . "\n";
echo "  - Database: " . $tenant->database_name . "\n";

// Create the database
$manager = $tenant->database()->manager();
$manager->setConnection('mysql');

if ($manager->databaseExists($tenant->database_name)) {
    echo "✓ Database already exists\n";
} else {
    $result = $manager->createDatabase($tenant);
    echo "✓ Database created: " . ($result ? 'YES' : 'NO') . "\n";
}

// Create a domain
$domain = \App\Models\Domain::create([
    'id' => \Illuminate\Support\Str::uuid(),
    'domain' => 'test-tenant2.localhost',
    'tenant_id' => $tenant->id,
    'is_primary' => true,
]);

echo "✓ Domain created:\n";
echo "  - Domain: " . $domain->domain . "\n";
echo "  - Tenant ID: " . $domain->tenant_id . "\n";

echo "\n=== Setup Complete ===\n";
echo "Now you can test by visiting:\n";
echo "http://test-tenant2.localhost/test-tenancy\n";
echo "\nMake sure to add 'test-tenant2.localhost' to your hosts file:\n";
echo "127.0.0.1 test-tenant2.localhost\n";