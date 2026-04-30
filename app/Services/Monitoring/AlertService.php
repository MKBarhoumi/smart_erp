<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    protected array $alerts = [];

    public function alert(string $type, string $message, array $context = []): void
    {
        $alert = [
            'type' => $type,
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->alerts[] = $alert;

        Log::channel('monitoring')->error($message, array_merge($context, ['alert_type' => $type]));

        $this->sendAlert($alert);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->alert('critical', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->alert('warning', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->alert('info', $message, $context);
    }

    protected function sendAlert(array $alert): void
    {
        if (!config('monitoring.enabled')) {
            return;
        }

        $email = config('monitoring.alerts.email');
        
        if ($email && in_array($alert['type'], ['critical', 'warning'])) {
            try {
                Mail::raw(
                    $this->formatAlertEmail($alert),
                    function ($message) use ($email, $alert) {
                        $message->to($email)
                            ->subject("[{$alert['type']}] {$alert['message']}");
                    }
                );
            } catch (\Throwable $e) {
                Log::error('Failed to send alert email', [
                    'error' => $e->getMessage(),
                    'alert' => $alert,
                ]);
            }
        }
    }

    protected function formatAlertEmail(array $alert): string
    {
        $lines = [
            "Alert Type: {$alert['type']}",
            "Message: {$alert['message']}",
            "Timestamp: {$alert['timestamp']}",
            '',
            'Context:',
        ];

        foreach ($alert['context'] as $key => $value) {
            $lines[] = "  {$key}: " . json_encode($value);
        }

        return implode("\n", $lines);
    }

    public function getRecentAlerts(int $limit = 10): array
    {
        return array_slice(array_reverse($this->alerts), 0, $limit);
    }

    public function clearAlerts(): void
    {
        $this->alerts = [];
    }
}