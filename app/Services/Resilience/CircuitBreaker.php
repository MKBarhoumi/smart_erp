<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    protected string $service;
    protected int $failures = 0;
    protected int $threshold;
    protected int $cooldown;
    protected string $lastFailureTime;

    public function __construct(string $service)
    {
        $this->service = $service;
        $this->threshold = config('resilience.circuit_breaker.threshold', 5);
        $this->cooldown = config('resilience.circuit_breaker.cooldown', 60);
        $this->loadState();
    }

    public function call(callable $callback)
    {
        if ($this->isOpen()) {
            if ($this->shouldAttemptReset()) {
                $this->attemptReset();
            } else {
                throw new \Exception("Circuit is OPEN for service: {$this->service}");
            }
        }

        try {
            $result = $callback();
            $this->reset();
            return $result;
        } catch (\Throwable $e) {
            $this->recordFailure();
            throw $e;
        }
    }

    protected function isOpen(): bool
    {
        return $this->failures >= $this->threshold;
    }

    protected function shouldAttemptReset(): bool
    {
        if (!$this->lastFailureTime) {
            return false;
        }

        return now()->diffInSeconds($this->lastFailureTime) >= $this->cooldown;
    }

    protected function attemptReset(): void
    {
        $this->failures = $this->threshold - 1;
        $this->saveState();
    }

    protected function reset(): void
    {
        $this->failures = 0;
        $this->lastFailureTime = null;
        $this->saveState();
    }

    protected function recordFailure(): void
    {
        $this->failures++;
        $this->lastFailureTime = now()->toDateTimeString();
        $this->saveState();
    }

    protected function loadState(): void
    {
        $state = Cache::get("circuit_breaker:{$this->service}", [
            'failures' => 0,
            'last_failure_time' => null,
        ]);

        $this->failures = $state['failures'];
        $this->lastFailureTime = $state['last_failure_time'];
    }

    protected function saveState(): void
    {
        Cache::put("circuit_breaker:{$this->service}", [
            'failures' => $this->failures,
            'last_failure_time' => $this->lastFailureTime,
        ], now()->addHours(24));
    }

    public static function for(string $service): self
    {
        return new self($service);
    }
}