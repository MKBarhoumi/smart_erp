<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\Plan;
use App\Models\TenantBillingEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MRRTrackingService
{
    /**
     * Calculate Monthly Recurring Revenue (MRR).
     */
    public function calculateMRR(): float
    {
        return Tenant::whereHas('subscriptions', function ($query) {
            $query->where('stripe_status', 'active');
        })->get()->sum(function ($tenant) {
            return $this->getTenantMRR($tenant);
        });
    }

    /**
     * Calculate MRR for a specific tenant.
     */
    public function getTenantMRR(Tenant $tenant): float
    {
        if (!$tenant->plan || $tenant->plan->isFree()) {
            return 0;
        }

        $price = $tenant->plan->price / 100; // Convert cents to dollars

        // Adjust for billing interval
        if ($tenant->plan->interval === 'year') {
            return $price / 12; // Monthly equivalent
        }

        return $price;
    }

    /**
     * Calculate Annual Recurring Revenue (ARR).
     */
    public function calculateARR(): float
    {
        return $this->calculateMRR() * 12;
    }

    /**
     * Get MRR by plan.
     */
    public function getMRRByPlan(): array
    {
        $plans = Plan::active()->get();
        $mrrByPlan = [];

        foreach ($plans as $plan) {
            $tenants = Tenant::where('plan_id', $plan->id)
                ->whereHas('subscriptions', function ($query) {
                    $query->where('stripe_status', 'active');
                })->get();

            $mrr = $tenants->count() * ($plan->price / 100);

            $mrrByPlan[$plan->slug] = [
                'plan_name' => $plan->name,
                'tenant_count' => $tenants->count(),
                'mrr' => $mrr,
                'arr' => $mrr * 12,
            ];
        }

        return $mrrByPlan;
    }

    /**
     * Get MRR growth over time.
     */
    public function getMRRGrowth(int $months = 12): array
    {
        $growth = [];

        for ($i = $months; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $mrr = TenantBillingEvent::subscriptions()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('event_type', 'like', '%created%')
                ->get()
                ->sum(function ($event) {
                    $tenant = Tenant::find($event->tenant_id);
                    return $tenant ? $this->getTenantMRR($tenant) : 0;
                });

            $growth[] = [
                'month' => $date->format('Y-m'),
                'mrr' => $mrr,
            ];
        }

        return $growth;
    }

    /**
     * Get churn rate.
     */
    public function getChurnRate(int $months = 1): float
    {
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Get active tenants at start of period
        $startTenants = Tenant::whereHas('subscriptions', function ($query) use ($startDate) {
            $query->where('stripe_status', 'active')
                ->where('created_at', '<', $startDate);
        })->count();

        if ($startTenants === 0) {
            return 0;
        }

        // Get cancelled tenants during period
        $cancelledTenants = TenantBillingEvent::subscriptions()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('event_type', 'like', '%deleted%')
            ->distinct('tenant_id')
            ->count();

        return ($cancelledTenants / $startTenants) * 100;
    }

    /**
     * Get customer lifetime value (CLV).
     */
    public function getAverageCLV(): float
    {
        $tenants = Tenant::whereHas('subscriptions')->get();

        if ($tenants->isEmpty()) {
            return 0;
        }

        $totalRevenue = $tenants->sum(function ($tenant) {
            return TenantBillingEvent::forTenant($tenant->id)
                ->payments()
                ->where('is_successful', true)
                ->sum('amount');
        });

        return $totalRevenue / $tenants->count();
    }

    /**
     * Get revenue by month.
     */
    public function getRevenueByMonth(int $months = 12): array
    {
        $revenue = [];

        for ($i = $months; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $monthlyRevenue = TenantBillingEvent::payments()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('is_successful', true)
                ->sum('amount');

            $revenue[] = [
                'month' => $date->format('Y-m'),
                'revenue' => $monthlyRevenue,
            ];
        }

        return $revenue;
    }

    /**
     * Get active subscribers count.
     */
    public function getActiveSubscribersCount(): int
    {
        return Tenant::whereHas('subscriptions', function ($query) {
            $query->where('stripe_status', 'active');
        })->count();
    }

    /**
     * Get trial subscribers count.
     */
    public function getTrialSubscribersCount(): int
    {
        return Tenant::where('billing_status', 'trial')
            ->where('trial_ends_at', '>', now())
            ->count();
    }

    /**
     * Get past due subscribers count.
     */
    public function getPastDueSubscribersCount(): int
    {
        return Tenant::where('billing_status', 'past_due')->count();
    }

    /**
     * Get cancelled subscribers count.
     */
    public function getCancelledSubscribersCount(): int
    {
        return Tenant::where('billing_status', 'cancelled')->count();
    }

    /**
     * Get comprehensive billing metrics.
     */
    public function getBillingMetrics(): array
    {
        return [
            'mrr' => $this->calculateMRR(),
            'arr' => $this->calculateARR(),
            'active_subscribers' => $this->getActiveSubscribersCount(),
            'trial_subscribers' => $this->getTrialSubscribersCount(),
            'past_due_subscribers' => $this->getPastDueSubscribersCount(),
            'cancelled_subscribers' => $this->getCancelledSubscribersCount(),
            'churn_rate' => $this->getChurnRate(),
            'average_clv' => $this->getAverageCLV(),
            'mrr_by_plan' => $this->getMRRByPlan(),
            'revenue_by_month' => $this->getRevenueByMonth(),
            'mrr_growth' => $this->getMRRGrowth(),
        ];
    }
}