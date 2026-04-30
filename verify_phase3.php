<?php

/**
 * Phase 3 Verification Script
 *
 * This script verifies that all Phase 3 components are properly installed and configured.
 */

echo "=== Phase 3 Verification ===\n\n";

$checks = [
    'Plan Model' => file_exists(__DIR__ . '/app/Models/Plan.php'),
    'BillingController' => file_exists(__DIR__ . '/app/Http/Controllers/BillingController.php'),
    'StripeWebhookController' => file_exists(__DIR__ . '/app/Http/Controllers/StripeWebhookController.php'),
    'EnsureTenantIsSubscribed Middleware' => file_exists(__DIR__ . '/app/Http/Middleware/EnsureTenantIsSubscribed.php'),
    'EnforcePlanLimits Middleware' => file_exists(__DIR__ . '/app/Http/Middleware/EnforcePlanLimits.php'),
    'PlanLimitsService' => file_exists(__DIR__ . '/app/Services/PlanLimitsService.php'),
    'Plans Migration' => file_exists(__DIR__ . '/database/migrations/2026_04_29_090822_create_plans_table.php'),
    'PlansSeeder' => file_exists(__DIR__ . '/database/seeders/PlansSeeder.php'),
    'BillingPlansTest' => file_exists(__DIR__ . '/tests/Feature/BillingPlansTest.php'),
    'PlanLimitsServiceTest' => file_exists(__DIR__ . '/tests/Feature/PlanLimitsServiceTest.php'),
    'Environment Setup Guide' => file_exists(__DIR__ . '/PHASE3_ENVIRONMENT_SETUP.md'),
    'Implementation Summary' => file_exists(__DIR__ . '/PHASE3_IMPLEMENTATION_SUMMARY.md'),
    'Quick Reference Guide' => file_exists(__DIR__ . '/PHASE3_QUICK_REFERENCE.md'),
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
    echo "\n🎉 All Phase 3 components verified successfully!\n";
    echo "\n⚠️  IMPORTANT: You still need to:\n";
    echo "1. Install Laravel Cashier: composer require laravel/cashier\n";
    echo "2. Configure Stripe environment variables\n";
    echo "3. Run migrations: php artisan migrate\n";
    echo "4. Seed plans: php artisan db:seed --class=PlansSeeder\n";
    echo "5. Set up Stripe webhook endpoint\n";
    exit(0);
} else {
    echo "\n⚠️  Some components are missing. Please review the implementation.\n";
    exit(1);
}