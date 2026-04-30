<?php

/**
 * Soft Launch Tenant Creation Script
 * 
 * This script creates test tenants for soft launch phase
 * with proper monitoring and tracking enabled.
 */

require __DIR__.'/../../vendor/autoload.php';

use App\Models\Tenant;
use App\Models\Domain;
use App\Services\TenantProvisioningService;
use App\Services\BusinessIntelligence\BusinessIntelligenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🚀 Soft Launch Tenant Creation\n";
echo "================================\n\n";

$testTenants = [
    [
        'name' => 'Test Company Alpha',
        'slug' => 'test-alpha',
        'email' => 'alpha@test.com',
        'plan' => 'pro',
    ],
    [
        'name' => 'Test Company Beta',
        'slug' => 'test-beta',
        'email' => 'beta@test.com',
        'plan' => 'enterprise',
    ],
    [
        'name' => 'Test Company Gamma',
        'slug' => 'test-gamma',
        'email' => 'gamma@test.com',
        'plan' => 'starter',
    ],
    [
        'name' => 'Test Company Delta',
        'slug' => 'test-delta',
        'email' => 'delta@test.com',
        'plan' => 'pro',
    ],
    [
        'name' => 'Test Company Epsilon',
        'slug' => 'test-epsilon',
        'email' => 'epsilon@test.com',
        'plan' => 'enterprise',
    ],
];

$provisioningService = new TenantProvisioningService();
$biService = new BusinessIntelligenceService();

$createdCount = 0;
$failedCount = 0;

foreach ($testTenants as $tenantData) {
    echo "📦 Creating tenant: {$tenantData['name']}\n";
    
    try {
        DB::beginTransaction();
        
        // Create tenant
        $tenant = $provisioningService->createTenant([
            'name' => $tenantData['name'],
            'slug' => $tenantData['slug'],
            'email' => $tenantData['email'],
            'plan_id' => $tenantData['plan'],
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(30),
        ]);
        
        echo "✅ Tenant created: {$tenant->id}\n";
        
        // Create domain
        $domain = $provisioningService->createDomain(
            $tenant,
            "{$tenantData['slug']}.localhost",
            true
        );
        
        echo "✅ Domain created: {$domain->domain}\n";
        
        // Track business intelligence event
        $biService->trackEvent('tenant.created', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'plan' => $tenantData['plan'],
            'source' => 'soft_launch',
        ]);
        
        echo "✅ BI event tracked\n";
        
        DB::commit();
        
        $createdCount++;
        echo "✅ Tenant creation completed\n\n";
        
    } catch (\Throwable $e) {
        DB::rollBack();
        $failedCount++;
        echo "❌ Tenant creation failed: {$e->getMessage()}\n\n";
        
        Log::error('Soft launch tenant creation failed', [
            'tenant_name' => $tenantData['name'],
            'error' => $e->getMessage(),
        ]);
    }
}

echo "================================\n";
echo "📊 Soft Launch Summary:\n";
echo "  - Total tenants: " . count($testTenants) . "\n";
echo "  - Created: $createdCount\n";
echo "  - Failed: $failedCount\n";
echo "  - Success rate: " . ($createdCount / count($testTenants) * 100) . "%\n";
echo "================================\n";

if ($createdCount > 0) {
    echo "\n🎉 Soft launch tenants created successfully!\n";
    echo "📋 Next steps:\n";
    echo "  1. Send login credentials to test users\n";
    echo "  2. Monitor tenant activity\n";
    echo "  3. Collect feedback\n";
    echo "  4. Track conversion metrics\n";
} else {
    echo "\n❌ No tenants were created. Please check the logs.\n";
}