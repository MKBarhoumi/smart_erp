<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\Plan;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class PlanLimitsService
{
    /**
     * Check if tenant can create a new user.
     */
    public function canCreateUser(Tenant $tenant): bool
    {
        return $this->checkLimit($tenant, 'users', User::count());
    }

    /**
     * Check if tenant can create a new customer.
     */
    public function canCreateCustomer(Tenant $tenant): bool
    {
        return $this->checkLimit($tenant, 'customers', Customer::count());
    }

    /**
     * Check if tenant can create a new product.
     */
    public function canCreateProduct(Tenant $tenant): bool
    {
        return $this->checkLimit($tenant, 'products', Product::count());
    }

    /**
     * Check if tenant can create a new invoice.
     */
    public function canCreateInvoice(Tenant $tenant): bool
    {
        return $this->checkLimit($tenant, 'invoices', Invoice::count());
    }

    /**
     * Check if tenant has reached a specific limit.
     */
    public function hasReachedLimit(Tenant $tenant, string $feature): bool
    {
        if (!$tenant->plan) {
            return false;
        }

        $limit = $tenant->plan->getLimit($feature);

        if ($limit === null) {
            return false;
        }

        $currentCount = $this->getCurrentCount($feature);

        return $currentCount >= $limit;
    }

    /**
     * Check if tenant is approaching limit (soft limit warning).
     */
    public function isApproachingLimit(Tenant $tenant, string $feature, float $threshold = 0.8): bool
    {
        if (!$tenant->plan) {
            return false;
        }

        $limit = $tenant->plan->getLimit($feature);

        if ($limit === null) {
            return false;
        }

        $currentCount = $this->getCurrentCount($feature);

        return ($currentCount / $limit) >= $threshold;
    }

    /**
     * Get limit warning status.
     */
    public function getLimitWarning(Tenant $tenant, string $feature): ?array
    {
        if (!$tenant->plan) {
            return null;
        }

        $limit = $tenant->plan->getLimit($feature);

        if ($limit === null) {
            return null;
        }

        $currentCount = $this->getCurrentCount($feature);
        $percentage = ($currentCount / $limit) * 100;

        if ($percentage >= 100) {
            return [
                'status' => 'critical',
                'message' => "You have reached your limit for {$feature}",
                'current' => $currentCount,
                'limit' => $limit,
                'percentage' => $percentage,
            ];
        } elseif ($percentage >= 80) {
            return [
                'status' => 'warning',
                'message' => "You are approaching your limit for {$feature}",
                'current' => $currentCount,
                'limit' => $limit,
                'percentage' => $percentage,
            ];
        }

        return null;
    }

    /**
     * Get current usage for a feature.
     */
    public function getCurrentUsage(string $feature): int
    {
        return $this->getCurrentCount($feature);
    }

    /**
     * Get limit for a feature.
     */
    public function getLimit(Tenant $tenant, string $feature): ?int
    {
        if (!$tenant->plan) {
            return null;
        }

        return $tenant->plan->getLimit($feature);
    }

    /**
     * Get remaining capacity for a feature.
     */
    public function getRemainingCapacity(Tenant $tenant, string $feature): ?int
    {
        if (!$tenant->plan) {
            return null;
        }

        $limit = $tenant->plan->getLimit($feature);

        if ($limit === null) {
            return null;
        }

        $currentCount = $this->getCurrentCount($feature);
        $remaining = $limit - $currentCount;

        return max(0, $remaining);
    }

    /**
     * Get usage percentage for a feature.
     */
    public function getUsagePercentage(Tenant $tenant, string $feature): ?float
    {
        if (!$tenant->plan) {
            return null;
        }

        $limit = $tenant->plan->getLimit($feature);

        if ($limit === null || $limit === 0) {
            return null;
        }

        $currentCount = $this->getCurrentCount($feature);

        return ($currentCount / $limit) * 100;
    }

    /**
     * Get all usage statistics for a tenant.
     */
    public function getUsageStatistics(Tenant $tenant): array
    {
        if (!$tenant->plan) {
            return [];
        }

        $features = ['users', 'customers', 'products', 'invoices'];
        $statistics = [];

        foreach ($features as $feature) {
            $currentCount = $this->getCurrentCount($feature);
            $limit = $tenant->plan->getLimit($feature);

            $statistics[$feature] = [
                'current' => $currentCount,
                'limit' => $limit,
                'remaining' => $limit !== null ? max(0, $limit - $currentCount) : null,
                'percentage' => $limit !== null && $limit > 0 ? ($currentCount / $limit) * 100 : null,
                'has_limit' => $limit !== null,
            ];
        }

        return $statistics;
    }

    /**
     * Check if tenant can access a specific feature.
     */
    public function canAccessFeature(Tenant $tenant, string $feature): bool
    {
        if (!$tenant->plan) {
            return false;
        }

        $features = $tenant->plan->features ?? [];

        return in_array($feature, $features, true);
    }

    /**
     * Get all features available to tenant.
     */
    public function getAvailableFeatures(Tenant $tenant): array
    {
        if (!$tenant->plan) {
            return [];
        }

        return $tenant->plan->features ?? [];
    }

    /**
     * Check limit helper method.
     */
    protected function checkLimit(Tenant $tenant, string $feature, int $currentCount): bool
    {
        if (!$tenant->plan) {
            return false;
        }

        $limit = $tenant->plan->getLimit($feature);

        if ($limit === null) {
            return true;
        }

        return $currentCount < $limit;
    }

    /**
     * Get current count helper method.
     */
    protected function getCurrentCount(string $feature): int
    {
        return match($feature) {
            'users' => User::count(),
            'customers' => Customer::count(),
            'products' => Product::count(),
            'invoices' => Invoice::count(),
            default => 0,
        };
    }

    /**
     * Enforce limit and throw exception if reached.
     */
    public function enforceLimit(Tenant $tenant, string $feature): void
    {
        if ($this->hasReachedLimit($tenant, $feature)) {
            $limit = $this->getLimit($tenant, $feature);
            $current = $this->getCurrentUsage($feature);

            abort(403, "You have reached your plan limit for {$feature} ({$current}/{$limit}). Please upgrade your plan.");
        }
    }

    /**
     * Check if tenant needs to upgrade.
     */
    public function needsUpgrade(Tenant $tenant): bool
    {
        if (!$tenant->plan) {
            return true;
        }

        $features = ['users', 'customers', 'products', 'invoices'];

        foreach ($features as $feature) {
            if ($this->hasReachedLimit($tenant, $feature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get recommended plan for upgrade.
     */
    public function getRecommendedPlan(Tenant $tenant): ?Plan
    {
        if (!$tenant->plan) {
            return Plan::where('slug', 'pro')->first();
        }

        $currentPlan = $tenant->plan;

        if ($currentPlan->slug === 'free') {
            return Plan::where('slug', 'pro')->first();
        }

        if ($currentPlan->slug === 'pro') {
            return Plan::where('slug', 'enterprise')->first();
        }

        return null;
    }
}