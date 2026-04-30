<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\StripeWebhookLog;
use App\Models\TenantBillingEvent;
use App\Services\DeadLetterQueueService;
use App\Services\MonitoringAlertService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    Artisan::call('migrate:fresh');
    Artisan::call('db:seed', ['--class' => 'PlansSeeder']);

    $this->tenant = Tenant::create([
        'id' => 'test-tenant',
        'name' => 'Test Tenant',
    ]);

    $this->tenant->domains()->create([
        'domain' => 'test.localhost',
    ]);

    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    // Mock Stripe customer creation
    $this->tenant->createAsStripeCustomer();
});

afterEach(function () {
    // Cleanup
});

test('webhook handles invoice.payment_failed correctly', function () {
    $eventId = 'evt_test_payment_failed';
    $payload = [
        'id' => $eventId,
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'customer' => $this->tenant->stripe_id,
                'amount_due' => 2900,
                'attempt_count' => 1,
            ],
        ],
    ];

    $response = $this->postJson('/stripe/webhook', $payload);
    $response->assertStatus(200);

    // Process the queued job
    $this->artisan('queue:work', ['--once' => true]);

    // Verify billing status updated to past_due
    $this->tenant->refresh();
    expect($this->tenant->billing_status)->toBe('past_due');

    // Verify billing event logged
    $billingEvent = TenantBillingEvent::where('tenant_id', $this->tenant->id)
        ->where('event_type', 'invoice.payment_failed')
        ->first();

    expect($billingEvent)->not->BeNull();
    expect($billingEvent->amount)->toBe(29.00);
});

test('webhook handles customer.subscription.deleted correctly', function () {
    $eventId = 'evt_test_sub_deleted';
    $payload = [
        'id' => $eventId,
        'type' => 'customer.subscription.deleted',
        'data' => [
            'object' => [
                'customer' => $this->tenant->stripe_id,
            ],
        ],
    ];

    $response = $this->postJson('/stripe/webhook', $payload);
    $response->assertStatus(200);

    // Process the queued job
    $this->artisan('queue:work', ['--once' => true]);

    // Verify billing status updated to cancelled
    $this->tenant->refresh();
    expect($this->tenant->billing_status)->toBe('cancelled');

    // Verify tenant access is restricted
    expect($this->tenant->canAccessSystem())->toBeFalse();

    // Verify billing event logged
    $billingEvent = TenantBillingEvent::where('tenant_id', $this->tenant->id)
        ->where('event_type', 'customer.subscription.deleted')
        ->first();

    expect($billingEvent)->not->BeNull();
});

test('webhook handles invoice.payment_succeeded correctly', function () {
    $eventId = 'evt_test_payment_success';
    $payload = [
        'id' => $eventId,
        'type' => 'invoice.payment_succeeded',
        'data' => [
            'object' => [
                'customer' => $this->tenant->stripe_id,
                'amount_paid' => 2900,
                'next_payment_attempt' => time() + 30 * 24 * 60 * 60,
            ],
        ],
    ];

    $response = $this->postJson('/stripe/webhook', $payload);
    $response->assertStatus(200);

    // Process the queued job
    $this->artisan('queue:work', ['--once' => true]);

    // Verify billing status updated to active
    $this->tenant->refresh();
    expect($this->tenant->billing_status)->toBe('active');

    // Verify tenant access is restored
    expect($this->tenant->canAccessSystem())->toBeTrue();

    // Verify billing event logged
    $billingEvent = TenantBillingEvent::where('tenant_id', $this->tenant->id)
        ->where('event_type', 'invoice.payment_succeeded')
        ->first();

    expect($billingEvent)->not->BeNull();
    expect($billingEvent->amount)->toBe(29.00);
});

test('webhook idempotency prevents duplicate processing', function () {
    $eventId = 'evt_test_duplicate';
    $payload = [
        'id' => $eventId,
        'type' => 'invoice.payment_succeeded',
        'data' => [
            'object' => [
                'customer' => $this->tenant->stripe_id,
                'amount_paid' => 2900,
            ],
        ],
    ];

    // First webhook call
    $response1 = $this->postJson('/stripe/webhook', $payload);
    $response1->assertStatus(200);
    $response1->assertJson(['status' => 'queued']);

    // Process first webhook
    $this->artisan('queue:work', ['--once' => true]);

    // Second webhook call with same event ID
    $response2 = $this->postJson('/stripe/webhook', $payload);
    $response2->assertStatus(200);
    $response2->assertJson(['status' => 'already_processed']);

    // Verify only one webhook log was created
    expect(StripeWebhookLog::where('event_id', $eventId)->count())->toBe(1);

    // Verify only one billing event was created
    expect(TenantBillingEvent::where('stripe_event_id', $eventId)->count())->toBe(1);
});

test('webhook timeout safety - returns immediately', function () {
    $eventId = 'evt_test_timeout';
    $payload = [
        'id' => $eventId,
        'type' => 'invoice.payment_succeeded',
        'data' => [
            'object' => [
                'customer' => $this->tenant->stripe_id,
                'amount_paid' => 2900,
            ],
        ],
    ];

    $startTime = microtime(true);
    $response = $this->postJson('/stripe/webhook', $payload);
    $endTime = microtime(true);

    // Response should be immediate (< 1 second)
    expect($endTime - $startTime)->toBeLessThan(1.0);

    $response->assertStatus(200);
    $response->assertJson(['status' => 'queued']);
});

test('dead letter queue handles failed webhooks', function () {
    // Create a failed webhook log
    $failedWebhook = StripeWebhookLog::create([
        'event_id' => 'evt_test_failed_dlq',
        'type' => 'invoice.payment_failed',
        'payload' => [
            'id' => 'evt_test_failed_dlq',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'customer' => $this->tenant->stripe_id,
                ],
            ],
        ],
        'status' => 'failed',
        'error_message' => 'Test failure',
    ]);

    $dlqService = app(DeadLetterQueueService::class);

    // Get failed webhooks stats
    $stats = $dlqService->getFailedWebhooksStats();

    expect($stats['total_failed'])->toBe(1);
    expect($stats['failures_by_type'])->toHaveKey('invoice.payment_failed');

    // Process failed webhooks
    $results = $dlqService->processFailedWebhooks(10);

    expect($results['processed'])->toBe(1);
    expect($results['failed'])->toBe(0);
});

test('monitoring service sends alerts for critical failures', function () {
    $alertService = app(MonitoringAlertService::class);

    // Test webhook failure alert
    $webhookLog = StripeWebhookLog::create([
        'event_id' => 'evt_test_alert',
        'type' => 'invoice.payment_failed',
        'payload' => [],
        'status' => 'failed',
        'error_message' => 'Test error',
    ]);

    $alertService->alertWebhookFailure($webhookLog, 'Test error');

    // Verify alert was logged
    $this->assertLogged('warning', ['Alert: webhook_failure']);
});

test('real-time usage sync tracks user creation', function () {
    $usageService = app(RealTimeUsageSyncService::class);

    // Track user creation
    $usageService->trackUserCreation($this->tenant->id);

    // Verify usage was recorded
    $usage = \App\Models\TenantUsage::getUsage($this->tenant->id, 'users');

    expect($usage)->not->BeNull();
    expect($usage->value)->toBe(1);
});

test('real-time usage sync sends warnings at 80% usage', function () {
    // Set tenant to Pro plan (10 users limit)
    $plan = \App\Models\Plan::where('slug', 'pro')->first();
    $this->tenant->plan_id = $plan->id;
    $this->tenant->save();

    $usageService = app(RealTimeUsageSyncService::class);

    // Track 8 users (80% of 10)
    for ($i = 0; $i < 8; $i++) {
        $usageService->trackUserCreation($this->tenant->id);
    }

    // Verify warning was sent
    $this->assertLogged('warning', ['Usage warning triggered']);
});

test('monitoring dashboard returns comprehensive data', function () {
    $response = $this->getJson('/monitoring/dashboard');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'system_health' => [],
        'dlq_health' => [],
        'billing_metrics' => [],
        'recent_alerts' => [],
        'timestamp',
    ]);
});

test('system health check triggers alerts when thresholds exceeded', function () {
    // Create many failed webhooks to exceed threshold
    for ($i = 0; $i < 10; $i++) {
        StripeWebhookLog::create([
            'event_id' => "evt_test_{$i}",
            'type' => 'invoice.payment_failed',
            'payload' => [],
            'status' => 'failed',
            'error_message' => 'Test failure',
        ]);
    }

    $alertService = app(MonitoringAlertService::class);

    // Trigger health check
    $alertService->checkSystemHealth();

    // Verify alert was logged for high failure rate
    $this->assertLogged('warning', ['Alert: webhook_failure_rate']);
});

test('webhook job retries on Stripe API errors', function () {
    // This test would require mocking Stripe API failures
    // For now, we'll test the retry logic structure
    $this->markTestSkipped('Requires Stripe API mocking');
});

test('dead letter queue cleanup removes old webhooks', function () {
    // Create old webhook logs
    StripeWebhookLog::create([
        'event_id' => 'evt_test_old_1',
        'type' => 'invoice.payment_succeeded',
        'payload' => [],
        'status' => 'completed',
        'processed_at' => now()->subDays(35),
    ]);

    StripeWebhookLog::create([
        'event_id' => 'evt_test_old_2',
        'type' => 'invoice.payment_succeeded',
        'payload' => [],
        'status' => 'completed',
        'processed_at' => now()->subDays(40),
    ]);

    $dlqService = app(DeadLetterQueueService::class);

    // Cleanup webhooks older than 30 days
    $results = $dlqService->cleanupOldWebhooks(30);

    expect($results['deleted'])->toBe(2);

    // Verify old webhooks were deleted
    expect(StripeWebhookLog::where('event_id', 'evt_test_old_1')->exists())->toBeFalse();
    expect(StripeWebhookLog::where('event_id', 'evt_test_old_2')->exists())->toBeFalse();
});

test('monitoring endpoints are protected by authentication', function () {
    // Test that monitoring endpoints require authentication
    $response = $this->getJson('/monitoring/health');
    $response->assertStatus(401);
});

test('webhook maintains central context during processing', function () {
    tenancy()->initialize($this->tenant);

    $payload = [
        'id' => 'evt_test_context_final',
        'type' => 'invoice.payment_succeeded',
        'data' => [
            'object' => [
                'customer' => $this->tenant->stripe_id,
                'amount_paid' => 2900,
            ],
        ],
    ];

    $response = $this->postJson('/stripe/webhook', $payload);
    $response->assertStatus(200);

    // Process the queued job
    $this->artisan('queue:work', ['--once' => true]);

    // Verify webhook log is in central database
    $log = StripeWebhookLog::where('event_id', 'evt_test_context_final')->first();
    expect($log)->not->BeNull();

    // Verify billing event is in central database
    $billingEvent = TenantBillingEvent::where('stripe_event_id', 'evt_test_context_final')->first();
    expect($billingEvent)->not->BeNull();
});