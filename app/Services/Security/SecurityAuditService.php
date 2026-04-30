<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Log;

class SecurityAuditService
{
    protected array $auditLog = [];

    public function log(string $event, array $data = []): void
    {
        $entry = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];

        $this->auditLog[] = $entry;

        Log::channel('security')->info($event, $data);
    }

    public function logAuthentication(array $data): void
    {
        $this->log('authentication', [
            'user_id' => $data['user_id'] ?? null,
            'email' => $data['email'] ?? null,
            'ip' => $data['ip'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'success' => $data['success'] ?? false,
            'failure_reason' => $data['failure_reason'] ?? null,
        ]);
    }

    public function logAuthorization(array $data): void
    {
        $this->log('authorization', [
            'user_id' => $data['user_id'] ?? null,
            'action' => $data['action'] ?? null,
            'resource' => $data['resource'] ?? null,
            'authorized' => $data['authorized'] ?? false,
            'ip' => $data['ip'] ?? null,
        ]);
    }

    public function logDataAccess(array $data): void
    {
        $this->log('data_access', [
            'user_id' => $data['user_id'] ?? null,
            'resource_type' => $data['resource_type'] ?? null,
            'resource_id' => $data['resource_id'] ?? null,
            'action' => $data['action'] ?? 'read',
            'ip' => $data['ip'] ?? null,
        ]);
    }

    public function logDataModification(array $data): void
    {
        $this->log('data_modification', [
            'user_id' => $data['user_id'] ?? null,
            'resource_type' => $data['resource_type'] ?? null,
            'resource_id' => $data['resource_id'] ?? null,
            'action' => $data['action'] ?? 'update',
            'changes' => $data['changes'] ?? [],
            'ip' => $data['ip'] ?? null,
        ]);
    }

    public function logSecurityEvent(array $data): void
    {
        $this->log('security_event', [
            'event_type' => $data['event_type'] ?? null,
            'severity' => $data['severity'] ?? 'info',
            'description' => $data['description'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'ip' => $data['ip'] ?? null,
            'details' => $data['details'] ?? [],
        ]);
    }

    public function logRateLimitExceeded(array $data): void
    {
        $this->log('rate_limit_exceeded', [
            'user_id' => $data['user_id'] ?? null,
            'ip' => $data['ip'] ?? null,
            'endpoint' => $data['endpoint'] ?? null,
            'limit' => $data['limit'] ?? null,
            'attempts' => $data['attempts'] ?? null,
        ]);
    }

    public function logSuspiciousActivity(array $data): void
    {
        $this->log('suspicious_activity', [
            'user_id' => $data['user_id'] ?? null,
            'ip' => $data['ip'] ?? null,
            'activity_type' => $data['activity_type'] ?? null,
            'description' => $data['description'] ?? null,
            'risk_score' => $data['risk_score'] ?? null,
        ]);
    }

    public function getAuditLog(int $limit = 100): array
    {
        return array_slice(array_reverse($this->auditLog), 0, $limit);
    }

    public function clearAuditLog(): void
    {
        $this->auditLog = [];
    }
}