<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MetricsService
{
    protected string $prefix = 'metrics:';

    public function increment(string $metric, int $value = 1, array $tags = []): void
    {
        $key = $this->prefix . $metric;
        Cache::increment($key, $value);
        Cache::put($key . ':tags', $tags, now()->addHours(24));
    }

    public function decrement(string $metric, int $value = 1, array $tags = []): void
    {
        $key = $this->prefix . $metric;
        Cache::decrement($key, $value);
        Cache::put($key . ':tags', $tags, now()->addHours(24));
    }

    public function gauge(string $metric, float $value, array $tags = []): void
    {
        $key = $this->prefix . $metric;
        Cache::put($key, $value, now()->addHours(24));
        Cache::put($key . ':tags', $tags, now()->addHours(24));
    }

    public function timing(string $metric, float $value, array $tags = []): void
    {
        $key = $this->prefix . $metric;
        $current = Cache::get($key, 0);
        $count = Cache::get($key . ':count', 0);
        
        Cache::put($key, $current + $value, now()->addHours(24));
        Cache::put($key . ':count', $count + 1, now()->addHours(24));
        Cache::put($key . ':tags', $tags, now()->addHours(24));
    }

    public function get(string $metric): ?float
    {
        return Cache::get($this->prefix . $metric);
    }

    public function getWithTags(string $metric): array
    {
        return [
            'value' => $this->get($metric),
            'tags' => Cache::get($this->prefix . $metric . ':tags', []),
        ];
    }

    public function getAll(): array
    {
        $metrics = [];
        $keys = Cache::get($this->prefix . '*');
        
        foreach ($keys as $key) {
            if (!str_ends_with($key, ':tags') && !str_ends_with($key, ':count')) {
                $metric = str_replace($this->prefix, '', $key);
                $metrics[$metric] = $this->getWithTags($metric);
            }
        }

        return $metrics;
    }

    public function reset(string $metric): void
    {
        Cache::forget($this->prefix . $metric);
        Cache::forget($this->prefix . $metric . ':tags');
        Cache::forget($this->prefix . $metric . ':count');
    }

    public function resetAll(): void
    {
        $keys = Cache::get($this->prefix . '*');
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    public function recordApiCall(string $endpoint, int $responseTime, int $statusCode): void
    {
        $this->timing('api.response_time', $responseTime, ['endpoint' => $endpoint]);
        $this->increment('api.calls', 1, ['endpoint' => $endpoint, 'status' => $statusCode]);
        
        if ($statusCode >= 500) {
            $this->increment('api.errors', 1, ['endpoint' => $endpoint, 'status' => $statusCode]);
        }
    }

    public function recordDatabaseQuery(string $query, float $duration): void
    {
        $this->timing('db.query_duration', $duration, ['query_type' => $this->getQueryType($query)]);
        $this->increment('db.queries', 1, ['query_type' => $this->getQueryType($query)]);
    }

    protected function getQueryType(string $query): string
    {
        $query = trim(strtoupper($query));
        if (str_starts_with($query, 'SELECT')) return 'SELECT';
        if (str_starts_with($query, 'INSERT')) return 'INSERT';
        if (str_starts_with($query, 'UPDATE')) return 'UPDATE';
        if (str_starts_with($query, 'DELETE')) return 'DELETE';
        return 'OTHER';
    }
}