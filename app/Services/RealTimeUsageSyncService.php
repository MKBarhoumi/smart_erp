<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantUsage;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class RealTimeUsageSyncService
{
    /**
     * Track user creation.
     */
    public function trackUserCreation(string $tenantId): void
    {
        try {
            tenancy()->end(); // Ensure central context

            $currentUsage = TenantUsage::getUsage($tenantId, 'users');
            $currentCount = $currentUsage ? $currentUsage->value : 0;

            TenantUsage::recordUsage($tenantId, 'users', $currentCount + 1);

            // Check for soft limit warning
            $this->checkUsageWarning($tenantId, 'users', $currentCount + 1);

            Log::debug("User creation tracked", [
                'tenant_id' => $tenantId,
                'total_users' => $currentCount + 1,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to track user creation", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track user deletion.
     */
    public function trackUserDeletion(string $tenantId): void
    {
        try {
            tenancy()->end(); // Ensure central context

            $currentUsage = TenantUsage::getUsage($tenantId, 'users');
            $currentCount = $currentUsage ? $currentUsage->value : 0;

            if ($currentCount > 0) {
                TenantUsage::recordUsage($tenantId, 'users', $currentCount - 1);
            }

            Log::debug("User deletion tracked", [
                'tenant_id' => $tenantId,
                'total_users' => max(0, $currentCount - 1),
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to track user deletion", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track customer creation.
     */
    public function trackCustomerCreation(string $tenantId): void
    {
        try {
            tenancy()->end(); // Ensure central context

            $currentUsage = TenantUsage::getUsage($tenantId, 'customers');
            $currentCount = $currentUsage ? $currentUsage->value : 0;

            TenantUsage::recordUsage($tenantId, 'customers', $currentCount + 1);

            // Check for soft limit warning
            $this->checkUsageWarning($tenantId, 'customers', $currentCount + 1);

            Log::debug("Customer creation tracked", [
                'tenant_id' => $tenantId,
                'total_customers' => $currentCount + 1,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to track customer creation", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track product creation.
     */
    public function trackProductCreation(string $tenantId): void
    {
        try {
            tenancy()->end(); // Ensure central context

            $currentUsage = TenantUsage::getUsage($tenantId, 'products');
            $currentCount = $currentUsage ? $currentUsage->value : 0;

            TenantUsage::recordUsage($tenantId, 'products', $currentCount + 1);

            // Check for soft limit warning
            $this->checkUsageWarning($tenantId, 'products', $currentCount + 1);

            Log::debug("Product creation tracked", [
                'tenant_id' => $tenantId,
                'total_products' => $currentCount + 1,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to track product creation", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track invoice creation.
     */
    public function trackInvoiceCreation(string $tenantId): void
    {
        try {
            tenancy()->end(); // Ensure central context

            $currentUsage = TenantUsage::getUsage($tenantId, 'invoices');
            $currentCount = $currentUsage ? $currentUsage->value : 0;

            TenantUsage::recordUsage($tenantId, 'invoices', $currentCount + 1);

            // Check for soft limit warning
            $this->checkUsageWarning($tenantId, 'invoices', $currentCount + 1);

            Log::debug("Invoice creation tracked", [
                'tenant_id' => $tenantId,
                'total_invoices' => $currentCount + 1,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to track invoice creation", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync all usage for a tenant.
     */
    public function syncAllUsage(string $tenantId): array
    {
        try {
            tenancy()->initialize(Tenant::find($tenantId));

            $usage = [
                'users' => User::count(),
                'customers' => Customer::count(),
                'products' => Product::count(),
                'invoices' => Invoice::count(),
            ];

            tenancy()->end();

            // Record all usage metrics
            foreach ($usage as $metric => $count) {
                TenantUsage::recordUsage($tenantId, $metric, $count);
            }

            Log::info("All usage synced for tenant", [
                'tenant_id' => $tenantId,
                'usage' => $usage,
            ]);

            return $usage;

        } catch (\Exception $e) {
            tenancy()->end();

            Log::error("Failed to sync all usage", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Check for usage warnings and send alerts.
     */
    protected function checkUsageWarning(string $tenantId, string $metric, int $currentCount): void
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant || !$tenant->plan) {
            return;
        }

        $limit = $tenant->plan->getLimit($metric);
        if ($limit === null) {
            return;
        }

        $percentage = ($currentCount / $limit) * 100;

        // Warning at 80%
        if ($percentage >= 80 && $percentage < 100) {
            $this->sendUsageWarning($tenant, $metric, $currentCount, $limit, $percentage);
        }

        // Critical at 95%
        if ($percentage >= 95) {
            $this->sendUsageCritical($tenant, $metric, $currentCount, $limit, $percentage);
        }
    }

    /**
     * Send usage warning alert.
     */
    protected function sendUsageWarning(Tenant $tenant, string $metric, int $current, int $limit, float $percentage): void
    {
        $alertService = app(MonitoringAlertService::class);

        $alertService->sendAlert('usage_warning', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'metric' => $metric,
            'current' => $current,
            'limit' => $limit,
            'percentage' => $percentage,
            'message' => "Usage warning: {$metric} at {$percentage}% of limit",
            'timestamp' => now()->toIso8601String(),
        ]);

        Log::warning("Usage warning triggered", [
            'tenant_id' => $tenant->id,
            'metric' => $metric,
            'percentage' => $percentage,
        ]);
    }

    /**
     * Send usage critical alert.
     */
    protected function sendUsageCritical(Tenant $tenant, string $metric, int $current, int $limit, float $percentage): void
    {
        $alertService = app(MonitoringAlertService::class);

        $alertService->sendAlert('usage_critical', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'metric' => $metric,
            'current' => $current,
            'limit' => $limit,
            'percentage' => $percentage,
            'message' => "Usage critical: {$metric} at {$percentage}% of limit",
            'timestamp' => now()->toIso8601String(),
        ]);

        Log::critical("Usage critical triggered", [
            'tenant_id' => $tenant->id,
            'metric' => $metric,
            'percentage' => $percentage,
        ]);
    }

    /**
     * Get real-time usage for a tenant.
     */
    public function getRealTimeUsage(string $tenantId): array
    {
        try {
            tenancy()->initialize(Tenant::find($tenantId));

            $usage = [
                'users' => User::count(),
                'customers' => Customer::count(),
                'products' => Product::count(),
                'invoices' => Invoice::count(),
            ];

            tenancy()->end();

            return $usage;

        } catch (\Exception $e) {
            tenancy()->end();

            Log::error("Failed to get real-time usage", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get usage with limits for a tenant.
     */
    public function getUsageWithLimits(string $tenantId): array
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant || !$tenant->plan) {
            return [];
        }

        $realTimeUsage = $this->getRealTimeUsage($tenantId);
        $usageWithLimits = [];

        foreach ($realTimeUsage as $metric => $count) {
            $limit = $tenant->plan->getLimit($metric);
            $percentage = $limit !== null ? ($count / $limit) * 100 : 0;

            $usageWithLimits[$metric] = [
                'current' => $count,
                'limit' => $limit,
                'percentage' => $percentage,
                'remaining' => $limit !== null ? max(0, $limit - $count) : null,
                'status' => $this->getUsageStatus($percentage, $limit),
            ];
        }

        return $usageWithLimits;
    }

    /**
     * Get usage status.
     */
    protected function getUsageStatus(float $percentage, ?int $limit): string
    {
        if ($limit === null) {
            return 'unlimited';
        }

        if ($percentage >= 100) {
            return 'critical';
        }

        if ($percentage >= 80) {
            return 'warning';
        }

        return 'ok';
    }
}