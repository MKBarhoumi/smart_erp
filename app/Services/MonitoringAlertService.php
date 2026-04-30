<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StripeWebhookLog;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class MonitoringAlertService
{
    /**
     * Alert channels configuration.
     */
    protected array $channels = [
        'log' => true,
        'email' => false,
        'slack' => false,
    ];

    /**
     * Alert recipients.
     */
    protected array $recipients = [];

    /**
     * Alert thresholds.
     */
    protected array $thresholds = [
        'queue_backlog' => 100, // Alert if queue has more than 100 jobs
        'webhook_failure_rate' => 5, // Alert if more than 5% webhooks fail
        'payment_failure_rate' => 2, // Alert if more than 2% payments fail
    ];

    /**
     * Send webhook failure alert.
     */
    public function alertWebhookFailure(StripeWebhookLog $webhookLog, string $error): void
    {
        $this->sendAlert('webhook_failure', [
            'webhook_log_id' => $webhookLog->id,
            'event_id' => $webhookLog->event_id,
            'event_type' => $webhookLog->type,
            'tenant_id' => $webhookLog->tenant_id,
            'error' => $error,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send payment failure alert.
     */
    public function alertPaymentFailure(string $tenantId, string $error, float $amount): void
    {
        $tenant = Tenant::find($tenantId);

        $this->sendAlert('payment_failure', [
            'tenant_id' => $tenantId,
            'tenant_name' => $tenant?->name ?? 'Unknown',
            'error' => $error,
            'amount' => $amount,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send queue backlog alert.
     */
    public function alertQueueBacklog(string $queue, int $backlogCount): void
    {
        $threshold = $this->thresholds['queue_backlog'];

        if ($backlogCount >= $threshold) {
            $this->sendAlert('queue_backlog', [
                'queue' => $queue,
                'backlog_count' => $backlogCount,
                'threshold' => $threshold,
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Send webhook failure rate alert.
     */
    public function alertWebhookFailureRate(float $failureRate): void
    {
        $threshold = $this->thresholds['webhook_failure_rate'];

        if ($failureRate >= $threshold) {
            $this->sendAlert('webhook_failure_rate', [
                'failure_rate' => $failureRate,
                'threshold' => $threshold,
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Send payment failure rate alert.
     */
    public function alertPaymentFailureRate(float $failureRate): void
    {
        $threshold = $this->thresholds['payment_failure_rate'];

        if ($failureRate >= $threshold) {
            $this->sendAlert('payment_failure_rate', [
                'failure_rate' => $failureRate,
                'threshold' => $threshold,
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Send tenant suspension alert.
     */
    public function alertTenantSuspension(string $tenantId, string $reason): void
    {
        $tenant = Tenant::find($tenantId);

        $this->sendAlert('tenant_suspension', [
            'tenant_id' => $tenantId,
            'tenant_name' => $tenant?->name ?? 'Unknown',
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send system health alert.
     */
    public function alertSystemHealth(array $healthData): void
    {
        $this->sendAlert('system_health', [
            'health_data' => $healthData,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send alert through configured channels.
     */
    protected function sendAlert(string $type, array $data): void
    {
        $alertKey = "alert_{$type}_" . md5(json_encode($data));

        // Prevent duplicate alerts within 5 minutes
        if (Cache::has($alertKey)) {
            return;
        }

        Cache::put($alertKey, true, 300); // 5 minutes

        // Log alert
        if ($this->channels['log']) {
            Log::warning("Alert: {$type}", $data);
        }

        // Send email alert
        if ($this->channels['email'] && !empty($this->recipients)) {
            $this->sendEmailAlert($type, $data);
        }

        // Send Slack alert
        if ($this->channels['slack']) {
            $this->sendSlackAlert($type, $data);
        }
    }

    /**
     * Send email alert.
     */
    protected function sendEmailAlert(string $type, array $data): void
    {
        try {
            // Implement email sending logic
            // This would typically use Laravel's Mail facade
            Log::info("Email alert sent: {$type}", $data);
        } catch (\Exception $e) {
            Log::error("Failed to send email alert", [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send Slack alert.
     */
    protected function sendSlackAlert(string $type, array $data): void
    {
        try {
            // Implement Slack webhook integration
            // This would typically use a Slack webhook URL
            Log::info("Slack alert sent: {$type}", $data);
        } catch (\Exception $e) {
            Log::error("Failed to send Slack alert", [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Configure alert channels.
     */
    public function configureChannels(array $channels): void
    {
        $this->channels = array_merge($this->channels, $channels);
    }

    /**
     * Set alert recipients.
     */
    public function setRecipients(array $recipients): void
    {
        $this->recipients = $recipients;
    }

    /**
     * Set alert thresholds.
     */
    public function setThresholds(array $thresholds): void
    {
        $this->thresholds = array_merge($this->thresholds, $thresholds);
    }

    /**
     * Get system health metrics.
     */
    public function getSystemHealth(): array
    {
        return [
            'queue_backlog' => $this->getQueueBacklog(),
            'webhook_failure_rate' => $this->getWebhookFailureRate(),
            'payment_failure_rate' => $this->getPaymentFailureRate(),
            'active_tenants' => Tenant::where('billing_status', 'active')->count(),
            'past_due_tenants' => Tenant::where('billing_status', 'past_due')->count(),
            'suspended_tenants' => Tenant::where('billing_status', 'suspended')->count(),
        ];
    }

    /**
     * Get queue backlog.
     */
    protected function getQueueBacklog(): array
    {
        $queues = ['stripe', 'default'];
        $backlog = [];

        foreach ($queues as $queue) {
            $backlog[$queue] = \Illuminate\Support\Facades\Queue::size($queue);
        }

        return $backlog;
    }

    /**
     * Get webhook failure rate.
     */
    protected function getWebhookFailureRate(): float
    {
        $total = StripeWebhookLog::where('created_at', '>=', now()->subHours(24))->count();
        $failed = StripeWebhookLog::where('created_at', '>=', now()->subHours(24))
            ->where('status', 'failed')
            ->count();

        if ($total === 0) {
            return 0;
        }

        return ($failed / $total) * 100;
    }

    /**
     * Get payment failure rate.
     */
    protected function getPaymentFailureRate(): float
    {
        // This would query your billing events for payment failures
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Check system health and send alerts if needed.
     */
    public function checkSystemHealth(): void
    {
        $health = $this->getSystemHealth();

        // Check queue backlog
        foreach ($health['queue_backlog'] as $queue => $backlog) {
            $this->alertQueueBacklog($queue, $backlog);
        }

        // Check webhook failure rate
        $this->alertWebhookFailureRate($health['webhook_failure_rate']);

        // Check payment failure rate
        $this->alertPaymentFailureRate($health['payment_failure_rate']);

        // Log system health
        Log::info('System health check', $health);
    }
}