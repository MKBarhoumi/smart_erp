<?php

namespace App\Http\Middleware\RateLimit;

use App\Services\RateLimiting\TenantRateLimiter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EnsureTenantRateLimit
{
    protected TenantRateLimiter $limiter;

    public function __construct()
    {
        $this->limiter = new TenantRateLimiter();
    }

    public function handle(Request $request, Closure $next, int $maxAttempts = 100, int $decayMinutes = 1)
    {
        if (!$this->limiter->attempt($request, $maxAttempts, $decayMinutes)) {
            return $this->buildResponse($request, $maxAttempts, $decayMinutes);
        }

        $response = $next($request);

        $this->addHeaders($response, $request, $maxAttempts);

        return $response;
    }

    protected function buildResponse(Request $request, int $maxAttempts, int $decayMinutes): Response
    {
        $retryAfter = $this->limiter->availableIn($request);

        return response()->json([
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', $retryAfter)
          ->header('X-RateLimit-Limit', $maxAttempts)
          ->header('X-RateLimit-Remaining', 0)
          ->header('X-RateLimit-Reset', now()->addSeconds($retryAfter)->getTimestamp());
    }

    protected function addHeaders(Response $response, Request $request, int $maxAttempts): void
    {
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $this->limiter->remaining($request, $maxAttempts));
    }
}