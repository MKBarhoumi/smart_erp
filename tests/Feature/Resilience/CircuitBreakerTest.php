<?php

namespace Tests\Feature\Resilience;

use Tests\TestCase;
use App\Services\Resilience\CircuitBreaker;
use Illuminate\Support\Facades\Cache;

class CircuitBreakerTest extends TestCase
{
    public function test_circuit_breaker_opens_after_threshold_failures(): void
    {
        $circuitBreaker = CircuitBreaker::for('test-service');
        $circuitBreaker->reset();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Circuit is OPEN');

        for ($i = 0; $i < 6; $i++) {
            try {
                $circuitBreaker->call(function () {
                    throw new \Exception('Service unavailable');
                });
            } catch (\Exception $e) {
                if ($i < 5) {
                    $this->assertStringContainsString('Service unavailable', $e->getMessage());
                } else {
                    throw $e;
                }
            }
        }
    }

    public function test_circuit_breaker_resets_after_successful_call(): void
    {
        $circuitBreaker = CircuitBreaker::for('test-service');
        $circuitBreaker->reset();

        $result = $circuitBreaker->call(function () {
            return 'success';
        });

        $this->assertEquals('success', $result);
        $this->assertEquals(0, $circuitBreaker->failures);
    }

    public function test_circuit_breaker_allows_reset_after_cooldown(): void
    {
        $circuitBreaker = CircuitBreaker::for('test-service');
        $circuitBreaker->reset();

        for ($i = 0; $i < 6; $i++) {
            try {
                $circuitBreaker->call(function () {
                    throw new \Exception('Service unavailable');
                });
            } catch (\Exception $e) {
                if ($i === 5) {
                    $this->assertStringContainsString('Circuit is OPEN', $e->getMessage());
                }
            }
        }

        $circuitBreaker->lastFailureTime = now()->subMinutes(2)->toDateTimeString();
        $circuitBreaker->saveState();

        $result = $circuitBreaker->call(function () {
            return 'success';
        });

        $this->assertEquals('success', $result);
    }
}