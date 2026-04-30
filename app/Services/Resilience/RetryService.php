<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Log;

class RetryService
{
    public static function run(callable $callback, int $tries = 3, int $delayMs = 100, ?callable $onRetry = null)
    {
        return retry($tries, function () use ($callback, $onRetry, $delayMs) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                if ($onRetry) {
                    $onRetry($e);
                }
                throw $e;
            }
        }, $delayMs);
    }

    public static function runWithBackoff(callable $callback, int $tries = 3, ?callable $onRetry = null)
    {
        $attempt = 0;

        while ($attempt < $tries) {
            $attempt++;
            try {
                return $callback();
            } catch (\Throwable $e) {
                if ($attempt >= $tries) {
                    throw $e;
                }

                $delay = $this->calculateBackoff($attempt);
                
                if ($onRetry) {
                    $onRetry($e, $attempt, $delay);
                }

                Log::warning("Retry attempt {$attempt}/{$tries} after {$delay}ms", [
                    'error' => $e->getMessage(),
                ]);

                usleep($delay * 1000);
            }
        }

        throw new \RuntimeException('Max retries exceeded');
    }

    protected function calculateBackoff(int $attempt): int
    {
        return min(1000 * pow(2, $attempt - 1), 10000);
    }
}