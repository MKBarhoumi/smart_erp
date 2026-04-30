<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MonitoringAlertService;
use App\Services\DeadLetterQueueService;
use App\Services\MRRTrackingService;
use App\Services\RealTimeUsageSyncService;
use App\Models\StripeWebhookLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MonitoringController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get system health overview.
     */
    public function health(): JsonResponse
    {
        ensureCentral();

        $alertService = app(MonitoringAlertService::class);
        $dlqService = app(DeadLetterQueueService::class);

        return response()->json([
            'system_health' => $alertService->getSystemHealth(),
            'dlq_health' => $dlqService->getDLQHealthMetrics(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get webhook monitoring data.
     */
    public function webhooks(): JsonResponse
    {
        ensureCentral();

        $dlqService = app(DeadLetterQueueService::class);

        return response()->json([
            'statistics' => $dlqService->getFailedWebhooksStats(),
            'stuck_webhooks' => $dlqService->getStuckWebhooks(),
            'recent_webhooks' => StripeWebhookLog::latest()
                ->limit(50)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'event_id' => $log->event_id,
                        'type' => $log->type,
                        'status' => $log->status,
                        'tenant_id' => $log->tenant_id,
                        'created_at' => $log->created_at->toIso8601String(),
                        'processed_at' => $log->processed_at?->toIso8601String(),
                    ];
                }),
        ]);
    }

    /**
     * Get billing metrics.
     */
    public function billingMetrics(): JsonResponse
    {
        ensureCentral();

        $mrrService = app(MRRTrackingService::class);

        return response()->json([
            'metrics' => $mrrService->getBillingMetrics(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get tenant usage data.
     */
    public function tenantUsage(Request $request): JsonResponse
    {
        ensureCentral();

        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $tenantId = $request->tenant_id;
        $usageService = app(RealTimeUsageSyncService::class);

        return response()->json([
            'real_time_usage' => $usageService->getRealTimeUsage($tenantId),
            'usage_with_limits' => $usageService->getUsageWithLimits($tenantId),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get DLQ report.
     */
    public function dlqReport(): JsonResponse
    {
        ensureCentral();

        $dlqService = app(DeadLetterQueueService::class);

        return response()->json($dlqService->generateDLQReport());
    }

    /**
     * Retry failed webhooks.
     */
    public function retryFailedWebhooks(Request $request): JsonResponse
    {
        ensureCentral();

        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $request->input('limit', 10);
        $dlqService = app(DeadLetterQueueService::class);

        $results = $dlqService->processFailedWebhooks($limit);

        return response()->json([
            'message' => 'Failed webhooks processed',
            'results' => $results,
        ]);
    }

    /**
     * Retry stuck webhooks.
     */
    public function retryStuckWebhooks(Request $request): JsonResponse
    {
        ensureCentral();

        $request->validate([
            'hours' => 'nullable|integer|min:1|max:24',
        ]);

        $hours = $request->input('hours', 1);
        $dlqService = app(DeadLetterQueueService::class);

        $results = $dlqService->retryStuckWebhooks($hours);

        return response()->json([
            'message' => 'Stuck webhooks retried',
            'results' => $results,
        ]);
    }

    /**
     * Sync tenant usage.
     */
    public function syncTenantUsage(Request $request): JsonResponse
    {
        ensureCentral();

        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $tenantId = $request->tenant_id;
        $usageService = app(RealTimeUsageSyncService::class);

        $usage = $usageService->syncAllUsage($tenantId);

        return response()->json([
            'message' => 'Usage synced successfully',
            'usage' => $usage,
        ]);
    }

    /**
     * Clean up old webhooks.
     */
    public function cleanupWebhooks(Request $request): JsonResponse
    {
        ensureCentral();

        $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $days = $request->input('days', 30);
        $dlqService = app(DeadLetterQueueService::class);

        $results = $dlqService->cleanupOldWebhooks($days);

        return response()->json([
            'message' => 'Old webhooks cleaned up',
            'results' => $results,
        ]);
    }

    /**
     * Trigger system health check.
     */
    public function triggerHealthCheck(): JsonResponse
    {
        ensureCentral();

        $alertService = app(MonitoringAlertService::class);
        $alertService->checkSystemHealth();

        return response()->json([
            'message' => 'System health check triggered',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get monitoring dashboard data.
     */
    public function dashboard(): JsonResponse
    {
        ensureCentral();

        $alertService = app(MonitoringAlertService::class);
        $dlqService = app(DeadLetterQueueService::class);
        $mrrService = app(MRRTrackingService::class);

        return response()->json([
            'system_health' => $alertService->getSystemHealth(),
            'dlq_health' => $dlqService->getDLQHealthMetrics(),
            'billing_metrics' => $mrrService->getBillingMetrics(),
            'recent_alerts' => $this->getRecentAlerts(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get recent alerts.
     */
    protected function getRecentAlerts(): array
    {
        // This would query your alerts table or logs
        // For now, return empty array as placeholder
        return [];
    }
}