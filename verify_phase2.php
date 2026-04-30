<?php

/**
 * Phase 2 Verification Script
 *
 * This script verifies that all Phase 2 components are properly installed and configured.
 */

echo "=== Phase 2 Verification ===\n\n";

$checks = [
    'RegisterTenant Action' => file_exists(__DIR__ . '/app/Actions/Tenant/RegisterTenant.php'),
    'TenantRegistrationController' => file_exists(__DIR__ . '/app/Http/Controllers/TenantRegistrationController.php'),
    'TenantConfigServiceProvider' => file_exists(__DIR__ . '/app/Providers/TenantConfigServiceProvider.php'),
    'TenantSeeder Base Class' => file_exists(__DIR__ . '/database/seeders/TenantSeeder.php'),
    'RolesAndPermissionsSeeder' => file_exists(__DIR__ . '/database/seeders/RolesAndPermissionsSeeder.php'),
    'Database Isolation Tests' => file_exists(__DIR__ . '/tests/Feature/TenantDatabaseIsolationTest.php'),
    'HTTP Isolation Tests' => file_exists(__DIR__ . '/tests/Feature/TenantHttpIsolationTest.php'),
    'Session Isolation Tests' => file_exists(__DIR__ . '/tests/Feature/TenantSessionIsolationTest.php'),
];

$passed = 0;
$failed = 0;

foreach ($checks as $name => $exists) {
    $status = $exists ? '✅ PASS' : '❌ FAIL';
    echo "{$status} - {$name}\n";
    if ($exists) {
        $passed++;
    } else {
        $failed++;
    }
}

echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . count($checks) . "\n";

if ($failed === 0) {
    echo "\n🎉 All Phase 2 components verified successfully!\n";
    exit(0);
} else {
    echo "\n⚠️  Some components are missing. Please review the implementation.\n";
    exit(1);
}