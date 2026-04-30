<?php

namespace App\Services\BusinessIntelligence;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BusinessIntelligenceService
{
    public function trackEvent(string $event, array $data = []): void
    {
        DB::connection('central')->table('business_events')->insert([
            'event_type' => $event,
            'tenant_id' => $data['tenant_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'properties' => json_encode($data),
            'created_at' => now(),
        ]);

        $this->updateMetrics($event, $data);
    }

    public function getConversionRate(string $fromEvent, string $toEvent, int $hours = 24): float
    {
        $fromCount = DB::connection('central')
            ->table('business_events')
            ->where('event_type', $fromEvent)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        $toCount = DB::connection('central')
            ->table('business_events')
            ->where('event_type', $toEvent)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        return $fromCount > 0 ? ($toCount / $fromCount) * 100 : 0;
    }

    public function getDropOffPoints(string $funnel, int $hours = 24): array
    {
        $funnelSteps = $this->getFunnelSteps($funnel);
        $dropOffs = [];

        for ($i = 0; $i < count($funnelSteps) - 1; $i++) {
            $currentStep = $funnelSteps[$i];
            $nextStep = $funnelSteps[$i + 1];

            $currentCount = DB::connection('central')
                ->table('business_events')
                ->where('event_type', $currentStep)
                ->where('created_at', '>=', now()->subHours($hours))
                ->count();

            $nextCount = DB::connection('central')
                ->table('business_events')
                ->where('event_type', $nextStep)
                ->where('created_at', '>=', now()->subHours($hours))
                ->count();

            $dropOffRate = $currentCount > 0 ? (($currentCount - $nextCount) / $currentCount) * 100 : 0;

            $dropOffs[] = [
                'from' => $currentStep,
                'to' => $nextStep,
                'drop_off_rate' => $dropOffRate,
                'users_lost' => $currentCount - $nextCount,
            ];
        }

        return $dropOffs;
    }

    public function getFeatureUsage(string $feature, int $hours = 24): array
    {
        $usage = DB::connection('central')
            ->table('business_events')
            ->where('event_type', 'feature_used')
            ->where('properties->feature', $feature)
            ->where('created_at', '>=', now()->subHours($hours))
            ->get();

        return [
            'total_uses' => $usage->count(),
            'unique_users' => $usage->pluck('user_id')->unique()->count(),
            'unique_tenants' => $usage->pluck('tenant_id')->unique()->count(),
        ];
    }

    public function getDailyMetrics(int $days = 30): array
    {
        $metrics = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            
            $metrics[$date] = [
                'tenant_registrations' => $this->getEventCount('tenant.created', $date),
                'invoice_created' => $this->getEventCount('invoice.created', $date),
                'checkout_started' => $this->getEventCount('checkout.started', $date),
                'checkout_completed' => $this->getEventCount('checkout.completed', $date),
                'subscription_created' => $this->getEventCount('subscription.created', $date),
                'subscription_cancelled' => $this->getEventCount('subscription.cancelled', $date),
            ];
        }

        return array_reverse($metrics);
    }

    public function getTenantMetrics(string $tenantId, int $days = 30): array
    {
        $metrics = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            
            $metrics[$date] = [
                'invoices_created' => $this->getEventCountForTenant('invoice.created', $tenantId, $date),
                'customers_added' => $this->getEventCountForTenant('customer.created', $tenantId, $date),
                'products_added' => $this->getEventCountForTenant('product.created', $tenantId, $date),
                'api_calls' => $this->getEventCountForTenant('api.call', $tenantId, $date),
            ];
        }

        return array_reverse($metrics);
    }

    protected function updateMetrics(string $event, array $data): void
    {
        $cacheKey = "metrics:{$event}:" . now()->format('Y-m-d');
        Cache::increment($cacheKey);
        Cache::expire($cacheKey, now()->addDays(30));
    }

    protected function getFunnelSteps(string $funnel): array
    {
        $funnels = [
            'registration' => ['tenant.created', 'user.created', 'first_login'],
            'onboarding' => ['first_login', 'company_setup', 'first_invoice'],
            'checkout' => ['checkout.started', 'payment_method_added', 'checkout.completed'],
        ];

        return $funnels[$funnel] ?? [];
    }

    protected function getEventCount(string $event, string $date): int
    {
        return DB::connection('central')
            ->table('business_events')
            ->where('event_type', $event)
            ->whereDate('created_at', $date)
            ->count();
    }

    protected function getEventCountForTenant(string $event, string $tenantId, string $date): int
    {
        return DB::connection('central')
            ->table('business_events')
            ->where('event_type', $event)
            ->where('tenant_id', $tenantId)
            ->whereDate('created_at', $date)
            ->count();
    }
}