<?php

namespace App\Services\BusinessIntelligence;

use Illuminate\Support\Facades\DB;

class ConversionTrackingService
{
    public function trackTenantCreated(string $tenantId, array $data = []): void
    {
        DB::connection('central')->table('conversion_events')->insert([
            'event_type' => 'tenant.created',
            'tenant_id' => $tenantId,
            'properties' => json_encode($data),
            'created_at' => now(),
        ]);
    }

    public function trackCheckoutStarted(string $tenantId, string $plan, array $data = []): void
    {
        DB::connection('central')->table('conversion_events')->insert([
            'event_type' => 'checkout.started',
            'tenant_id' => $tenantId,
            'properties' => json_encode(array_merge($data, ['plan' => $plan])),
            'created_at' => now(),
        ]);
    }

    public function trackCheckoutCompleted(string $tenantId, string $plan, float $amount, array $data = []): void
    {
        DB::connection('central')->table('conversion_events')->insert([
            'event_type' => 'checkout.completed',
            'tenant_id' => $tenantId,
            'properties' => json_encode(array_merge($data, [
                'plan' => $plan,
                'amount' => $amount,
            ])),
            'created_at' => now(),
        ]);
    }

    public function trackCheckoutFailed(string $tenantId, string $plan, string $reason, array $data = []): void
    {
        DB::connection('central')->table('conversion_events')->insert([
            'event_type' => 'checkout.failed',
            'tenant_id' => $tenantId,
            'properties' => json_encode(array_merge($data, [
                'plan' => $plan,
                'reason' => $reason,
            ])),
            'created_at' => now(),
        ]);
    }

    public function getConversionMetrics(int $hours = 24): array
    {
        $started = DB::connection('central')
            ->table('conversion_events')
            ->where('event_type', 'checkout.started')
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        $completed = DB::connection('central')
            ->table('conversion_events')
            ->where('event_type', 'checkout.completed')
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        $failed = DB::connection('central')
            ->table('conversion_events')
            ->where('event_type', 'checkout.failed')
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        return [
            'started' => $started,
            'completed' => $completed,
            'failed' => $failed,
            'conversion_rate' => $started > 0 ? ($completed / $started) * 100 : 0,
            'failure_rate' => $started > 0 ? ($failed / $started) * 100 : 0,
        ];
    }

    public function getPlanConversionRates(int $hours = 24): array
    {
        $plans = DB::connection('central')
            ->table('conversion_events')
            ->where('event_type', 'checkout.started')
            ->where('created_at', '>=', now()->subHours($hours))
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(properties, "$.plan")) as plan, COUNT(*) as count')
            ->groupBy('plan')
            ->get();

        $conversionRates = [];

        foreach ($plans as $plan) {
            $planName = $plan->plan;
            $started = $plan->count;

            $completed = DB::connection('central')
                ->table('conversion_events')
                ->where('event_type', 'checkout.completed')
                ->where('created_at', '>=', now()->subHours($hours))
                ->whereRaw('JSON_EXTRACT(properties, "$.plan") = ?', [$planName])
                ->count();

            $conversionRates[$planName] = [
                'started' => $started,
                'completed' => $completed,
                'conversion_rate' => $started > 0 ? ($completed / $started) * 100 : 0,
            ];
        }

        return $conversionRates;
    }
}