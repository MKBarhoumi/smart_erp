<?php

namespace App\Services\RateLimiting;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GlobalRateLimiter
{
    protected RateLimiter $limiter;

    public function __construct()
    {
        $this->limiter = app(RateLimiter::class);
    }

    public function attempt(Request $request, int $maxAttempts = 60, int $decayMinutes = 1): bool
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $this->logRateLimitExceeded($request, $maxAttempts, $decayMinutes);
            return false;
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        return true;
    }

    public function attempts(Request $request): int
    {
        return $this->limiter->attempts($this->resolveRequestSignature($request));
    }

    public function remaining(Request $request, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->attempts($request));
    }

    public function availableIn(Request $request): int
    {
        return $this->limiter->availableIn($this->resolveRequestSignature($request));
    }

    public function clear(Request $request): void
    {
        $this->limiter->clear($this->resolveRequestSignature($request));
    }

    protected function resolveRequestSignature(Request $request): string
    {
        return sha1(
            $request->ip() . '|' . $request->route()->getName() . '|' . $request->method()
        );
    }

    protected function logRateLimitExceeded(Request $request, int $maxAttempts, int $decayMinutes): void
    {
        Log::warning('Rate limit exceeded', [
            'ip' => $request->ip(),
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'max_attempts' => $maxAttempts,
            'decay_minutes' => $decayMinutes,
            'attempts' => $this->attempts($request),
        ]);
    }
}