<?php

namespace App\Services\Security;

class SensitiveDataService
{
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'api_key',
        'api_secret',
        'secret',
        'token',
        'access_token',
        'refresh_token',
        'stripe_secret',
        'stripe_key',
        'credit_card',
        'ssn',
        'social_security_number',
        'bank_account',
        'routing_number',
    ];

    protected array $sensitivePatterns = [
        '/\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b/', // Credit card
        '/\b\d{3}[-\s]?\d{2}[-\s]?\d{4}\b/', // SSN
        '/\b[A-Za-z0-9]{32,}\b/', // API keys/tokens
    ];

    public function hideSensitiveData(array $data): array
    {
        return $this->hideSensitiveDataRecursive($data);
    }

    protected function hideSensitiveDataRecursive($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $cleaned = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveField($key)) {
                $cleaned[$key] = $this->maskValue($value);
            } elseif (is_array($value)) {
                $cleaned[$key] = $this->hideSensitiveDataRecursive($value);
            } elseif (is_string($value) && $this->containsSensitiveData($value)) {
                $cleaned[$key] = $this->maskSensitiveString($value);
            } else {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }

    protected function isSensitiveField(string $field): bool
    {
        $normalizedField = strtolower($field);
        
        foreach ($this->sensitiveFields as $sensitiveField) {
            if (str_contains($normalizedField, $sensitiveField)) {
                return true;
            }
        }

        return false;
    }

    protected function containsSensitiveData(string $value): bool
    {
        foreach ($this->sensitivePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    protected function maskValue($value): string
    {
        if (is_null($value)) {
            return '***';
        }

        $stringValue = (string) $value;
        $length = strlen($stringValue);

        if ($length <= 4) {
            return '****';
        }

        return str_repeat('*', $length - 4) . substr($stringValue, -4);
    }

    protected function maskSensitiveString(string $value): string
    {
        foreach ($this->sensitivePatterns as $pattern) {
            $value = preg_replace_callback($pattern, function ($matches) {
                return $this->maskValue($matches[0]);
            }, $value);
        }

        return $value;
    }

    public function addSensitiveField(string $field): void
    {
        $this->sensitiveFields[] = strtolower($field);
    }

    public function addSensitivePattern(string $pattern): void
    {
        $this->sensitivePatterns[] = $pattern;
    }

    public function getSensitiveFields(): array
    {
        return $this->sensitiveFields;
    }

    public function getSensitivePatterns(): array
    {
        return $this->sensitivePatterns;
    }
}