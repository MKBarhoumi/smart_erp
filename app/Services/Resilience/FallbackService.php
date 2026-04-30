<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Log;

class FallbackService
{
    protected array $fallbacks = [];

    public function register(string $key, callable $fallback): self
    {
        $this->fallbacks[$key] = $fallback;
        return $this;
    }

    public function execute(string $key, callable $primary, array $context = [])
    {
        try {
            return $primary();
        } catch (\Throwable $e) {
            Log::warning("Primary operation failed, using fallback", [
                'key' => $key,
                'error' => $e->getMessage(),
                'context' => $context,
            ]);

            if (!isset($this->fallbacks[$key])) {
                throw new \RuntimeException("No fallback registered for key: {$key}");
            }

            return $this->fallbacks[$key]($e, $context);
        }
    }

    public function executeWithCircuitBreaker(string $key, callable $primary, string $service, array $context = [])
    {
        return CircuitBreaker::for($service)->call(function () use ($key, $primary, $context) {
            return $this->execute($key, $primary, $context);
        });
    }

    public static function create(): self
    {
        return new self();
    }
}