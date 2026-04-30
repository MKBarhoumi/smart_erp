<?php

namespace App\Services\Alerting;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackAlertService
{
    protected string $webhookUrl;
    protected string $channel;
    protected string $username;
    protected string $iconEmoji;

    public function __construct()
    {
        $this->webhookUrl = config('monitoring.slack_webhook');
        $this->channel = config('monitoring.slack_channel', '#alerts');
        $this->username = config('monitoring.slack_username', 'Smart ERP Monitor');
        $this->iconEmoji = config('monitoring.slack_icon', ':warning:');
    }

    public function sendAlert(string $message, array $context = [], string $level = 'warning'): void
    {
        if (empty($this->webhookUrl)) {
            Log::warning('Slack webhook URL not configured');
            return;
        }

        $color = $this->getColorForLevel($level);
        $emoji = $this->getEmojiForLevel($level);

        $payload = [
            'channel' => $this->channel,
            'username' => $this->username,
            'icon_emoji' => $emoji,
            'attachments' => [
                [
                    'color' => $color,
                    'title' => $this->getTitleForLevel($level),
                    'text' => $message,
                    'fields' => $this->formatContext($context),
                    'footer' => 'Smart ERP Monitoring',
                    'ts' => now()->timestamp,
                ],
            ],
        ];

        try {
            Http::post($this->webhookUrl, $payload);
            Log::info('Slack alert sent', ['message' => $message, 'level' => $level]);
        } catch (\Throwable $e) {
            Log::error('Failed to send Slack alert', [
                'error' => $e->getMessage(),
                'message' => $message,
            ]);
        }
    }

    public function sendCriticalAlert(string $message, array $context = []): void
    {
        $this->sendAlert($message, $context, 'critical');
    }

    public function sendWarningAlert(string $message, array $context = []): void
    {
        $this->sendAlert($message, $context, 'warning');
    }

    public function sendInfoAlert(string $message, array $context = []): void
    {
        $this->sendAlert($message, $context, 'info');
    }

    public function sendStripeWebhookAlert(int $failures, int $threshold): void
    {
        $this->sendCriticalAlert(
            "Stripe webhook failure rate exceeded threshold",
            [
                'failures' => $failures,
                'threshold' => $threshold,
                'time' => now()->toIso8601String(),
            ]
        );
    }

    public function sendQueueDelayAlert(int $delay, int $threshold): void
    {
        $this->sendWarningAlert(
            "Queue processing delay exceeded threshold",
            [
                'delay_seconds' => $delay,
                'threshold_seconds' => $threshold,
                'time' => now()->toIso8601String(),
            ]
        );
    }

    public function sendErrorRateAlert(float $errorRate, float $threshold): void
    {
        $this->sendCriticalAlert(
            "Application error rate exceeded threshold",
            [
                'error_rate' => $errorRate,
                'threshold' => $threshold,
                'time' => now()->toIso8601String(),
            ]
        );
    }

    public function sendDatabaseConnectionAlert(int $connections, int $max): void
    {
        $this->sendCriticalAlert(
            "Database connection pool nearly exhausted",
            [
                'active_connections' => $connections,
                'max_connections' => $max,
                'usage_percentage' => ($connections / $max) * 100,
                'time' => now()->toIso8601String(),
            ]
        );
    }

    public function sendQueueBacklogAlert(int $pending, int $threshold): void
    {
        $this->sendWarningAlert(
            "Queue backlog detected",
            [
                'pending_jobs' => $pending,
                'threshold' => $threshold,
                'time' => now()->toIso8601String(),
            ]
        );
    }

    protected function getColorForLevel(string $level): string
    {
        return match($level) {
            'critical' => '#FF0000',
            'warning' => '#FFA500',
            'info' => '#00FF00',
            default => '#808080',
        };
    }

    protected function getEmojiForLevel(string $level): string
    {
        return match($level) {
            'critical' => ':rotating_light:',
            'warning' => ':warning:',
            'info' => ':information_source:',
            default => ':bell:',
        };
    }

    protected function getTitleForLevel(string $level): string
    {
        return match($level) {
            'critical' => '🚨 CRITICAL ALERT',
            'warning' => '⚠️ WARNING',
            'info' => 'ℹ️ INFO',
            default => '🔔 ALERT',
        };
    }

    protected function formatContext(array $context): array
    {
        $fields = [];

        foreach ($context as $key => $value) {
            $fields[] = [
                'title' => ucwords(str_replace('_', ' ', $key)),
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'short' => true,
            ];
        }

        return $fields;
    }
}