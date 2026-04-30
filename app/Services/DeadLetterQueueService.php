<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StripeWebhookLog;
use App\Models\TenantBillingEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeadLetterQueueService
{
    /**
     * Process failed webhooks from dead letter queue.
     */
    public function processFailedWebhooks(int $limit = 10): array
    {
        $failedWebhooks = StripeWebhookLog::failed()
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        $results = [
            'processed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => [],
        ];

        foreach ($failedWebhooks as $webhookLog) {
            try {
                // Check if already processed
                if ($webhookLog->isProcessed()) {
                    $results['skipped']++;
                    $results['details'][] = [
                        'webhook_log_id' => $webhookLog->id,
                        'event_id' => $webhookLog->event_id,
                        'status' => 'already_processed',
                    ];
                    continue;
                }

                // Reset status for retry
                $webhookLog->update(['status' => 'pending']);

                // Dispatch for retry
                dispatch(new \App\Jobs\HandleStripeWebhook($webhookLog->id))
                    ->onQueue('stripe')
                    ->delay(now()->addSeconds(10));

                $results['processed']++;
                $results['details'][] = [
                    'webhook_log_id' => $webhookLog->id,
                    'event_id' => $webhookLog->event_id,
                    'status' => 'queued_for_retry',
                ];

                Log::info("Failed webhook queued for retry", [
                    'webhook_log_id' => $webhookLog->id,
                    'event_id' => $webhookLog->event_id,
                ]);

            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'webhook_log_id' => $webhookLog->id,
                    'event_id' => $webhookLog->event_id,
                    'error' => $e->getMessage(),
                ];

                Log::error("Failed to queue webhook for retry", [
                    'webhook_log_id' => $webhookLog->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Get failed webhooks statistics.
     */
    public function getFailedWebhooksStats(): array
    {
        $totalFailed = StripeWebhookLog::failed()->count();
        $totalProcessed = StripeWebhookLog::completed()->count();
        $totalPending = StripeWebhookLog::pending()->count();

        // Get failure rate by type
        $failuresByType = StripeWebhookLog::failed()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        // Get recent failures (last 24 hours)
        $recentFailures = StripeWebhookLog::failed()
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        return [
            'total_failed' => $totalFailed,
            'total_processed' => $totalProcessed,
            'total_pending' => $totalPending,
            'failures_by_type' => $failuresByType,
            'recent_failures' => $recentFailures,
            'failure_rate' => $totalProcessed > 0 ? ($totalFailed / ($totalFailed + $totalProcessed)) * 100 : 0,
        ];
    }

    /**
     * Get stuck webhooks (pending for too long).
     */
    public function getStuckWebhooks(int $hours = 1): array
    {
        return StripeWebhookLog::pending()
            ->where('created_at', '<', now()->subHours($hours))
            ->get()
            ->map(function ($webhookLog) {
                return [
                    'id' => $webhookLog->id,
                    'event_id' => $webhookLog->event_id,
                    'type' => $webhookLog->type,
                    'tenant_id' => $webhookLog->tenant_id,
                    'created_at' => $webhookLog->created_at,
                    'stuck_for_hours' => $webhookLog->created_at->diffInHours(now()),
                ];
            })
            ->toArray();
    }

    /**
     * Retry stuck webhooks.
     */
    public function retryStuckWebhooks(int $hours = 1): array
    {
        $stuckWebhooks = StripeWebhookLog::pending()
            ->where('created_at', '<', now()->subHours($hours))
            ->limit(50)
            ->get();

        $results = [
            'retried' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($stuckWebhooks as $webhookLog) {
            try {
                dispatch(new \App\Jobs\HandleStripeWebhook($webhookLog->id))
                    ->onQueue('stripe')
                    ->delay(now()->addSeconds(5));

                $results['retried']++;
                $results['details'][] = [
                    'webhook_log_id' => $webhookLog->id,
                    'event_id' => $webhookLog->event_id,
                    'status' => 'retried',
                ];

                Log::info("Stuck webhook retried", [
                    'webhook_log_id' => $webhookLog->id,
                    'event_id' => $webhookLog->event_id,
                ]);

            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'webhook_log_id' => $webhookLog->id,
                    'event_id' => $webhookLog->event_id,
                    'error' => $e->getMessage(),
                ];

                Log::error("Failed to retry stuck webhook", [
                    'webhook_log_id' => $webhookLog->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Clean up old processed webhooks.
     */
    public function cleanupOldWebhooks(int $days = 30): array
    {
        $cutoffDate = now()->subDays($days);

        $deleted = StripeWebhookLog::where('status', 'completed')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        Log::info("Cleaned up old webhooks", [
            'deleted_count' => $deleted,
            'cutoff_date' => $cutoffDate,
        ]);

        return [
            'deleted' => $deleted,
            'cutoff_date' => $cutoffDate->toIso8601String(),
        ];
    }

    /**
     * Get dead letter queue health metrics.
     */
    public function getDLQHealthMetrics(): array
    {
        $stats = $this->getFailedWebhooksStats();
        $stuckWebhooks = $this->getStuckWebhooks();

        return [
            'failed_webhooks' => $stats['total_failed'],
            'stuck_webhooks' => count($stuckWebhooks),
            'failure_rate' => $stats['failure_rate'],
            'recent_failures' => $stats['recent_failures'],
            'failures_by_type' => $stats['failures_by_type'],
            'health_status' => $this->getHealthStatus($stats, count($stuckWebhooks)),
        ];
    }

    /**
     * Get overall health status.
     */
    protected function getHealthStatus(array $stats, int $stuckCount): string
    {
        if ($stats['total_failed'] === 0 && $stuckCount === 0) {
            return 'healthy';
        }

        if ($stats['failure_rate'] > 10 || $stuckCount > 50) {
            return 'critical';
        }

        if ($stats['failure_rate'] > 5 || $stuckCount > 20) {
            return 'warning';
        }

        return 'degraded';
    }

    /**
     * Generate DLQ report.
     */
    public function generateDLQReport(): array
    {
        $stats = $this->getFailedWebhooksStats();
        $stuckWebhooks = $this->getStuckWebhooks();
        $healthMetrics = $this->getDLQHealthMetrics();

        return [
            'generated_at' => now()->toIso8601String(),
            'health_status' => $healthMetrics['health_status'],
            'statistics' => $stats,
            'stuck_webhooks' => $stuckWebhooks,
            'health_metrics' => $healthMetrics,
            'recommendations' => $this->getRecommendations($stats, $stuckWebhooks),
        ];
    }

    /**
     * Get recommendations based on DLQ status.
     */
    protected function getRecommendations(array $stats, array $stuckWebhooks): array
    {
        $recommendations = [];

        if ($stats['total_failed'] > 10) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Review failed webhooks',
                'description' => "There are {$stats['total_failed']} failed webhooks that need attention",
            ];
        }

        if (count($stuckWebhooks) > 5) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Retry stuck webhooks',
                'description' => "There are " . count($stuckWebhooks) . " webhooks stuck in pending state",
            ];
        }

        if ($stats['failure_rate'] > 5) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Investigate failure patterns',
                'description' => "Webhook failure rate is {$stats['failure_rate']}%, investigate common failure types",
            ];
        }

        if ($stats['recent_failures'] > 3) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Check recent webhook failures',
                'description' => "There have been {$stats['recent_failures']} webhook failures in the last 24 hours",
            ];
        }

        return $recommendations;
    }
}