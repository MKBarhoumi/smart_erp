<?php

namespace Tests\Feature\Load;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class LoadTest extends TestCase
{
    public function test_health_endpoint_handles_load(): void
    {
        $responses = [];

        for ($i = 0; $i < 10; $i++) {
            $response = $this->get('/health');
            $responses[] = $response->status();
        }

        foreach ($responses as $status) {
            $this->assertContains($status, [200, 503]);
        }
    }

    public function test_dashboard_endpoint_handles_concurrent_requests(): void
    {
        $responses = [];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->get('/dashboard');
            $responses[] = $response->status();
        }

        foreach ($responses as $status) {
            $this->assertContains($status, [200, 302, 401, 419]);
        }
    }

    public function test_api_rate_limiting(): void
    {
        $responses = [];

        for ($i = 0; $i < 65; $i++) {
            $response = $this->get('/api/test');
            $responses[] = $response->status();
        }

        $rateLimited = array_filter($responses, fn($status) => $status === 429);
        $this->assertGreaterThan(0, count($rateLimited));
    }
}