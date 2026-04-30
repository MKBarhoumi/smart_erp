<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\StripeWebhookLog;
use App\Models\TenantBillingEvent;
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

test('webhook is idempotent - duplicate events are not processed', function () {
    $eventId = 'evt_test123';
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

    // Second webhook call with same event ID
    $response2 = $this->postJson('/stripe/webhook', $payload);
    $response2->assertStatus(200);
    $response2->assertJson(['status' => 'already_processed']);

    // Verify only one webhook log was created
    expect(StripeWebhookLog::where('event_id', $eventId)->count())->toBe(1);
});

test('webhook creates log entry with correct data', function () {
    $eventId = 'evt_test456';
    $payload = [
        'id' => $eventId,
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'customer' => $this->tenant->stripe_id,
                'status' => 'trialing',
            ],
        ],
    ];

    $response = $this->postJson('/stripe/webhook', $payload);
    $response->assertStatus(200);

    $log = StripeWebhookLog::where('event_id', $eventId)->first();

    expect($log)->not->toBeNull();
    expect($log->type)->toBe('customer.subscription.created');
    expect($log->status)->toBe('pending');
    expect($log->tenant_id)->toBe($this->tenant->id);
    expect($log->payload)->toBeArray();
});

test('webhook queues processing job', function () {
    Queue::fake();

    $payload = [
        'id' => 'evt_test789',
        'type' => 'invoice.payment_succeeded',
        'data' => [
            'object' => [
                'customer' => $this->tenant->stripe_id,
                'amount_paid' => 2900,
            ],
        ],
    ];

    $this->postJson('/stripe/webhook', $payload);

    Queue::assertPushed(\App\Jobs\HandleStripeWebhook::class);
});

test('webhook processes invoice.payment_succeeded', function () {
    $eventId = 'evt_test_invoice_success';
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

    // Verify billing status updated
    $this->tenant->refresh();
    expect($this->tenant->billing_status)->toBe('active');
    expect($this->tenant->last_payment_at)->not->toBeNull();

    // Verify billing event logged
    $billingEvent = TenantBillingEvent::where('tenant_id', $this->tenant->id)
        ->where('event_type', 'invoice.payment_succeeded')
        ->first();

    expect($billingEvent)->not->BeNull();
    expect($billingEvent->amount)->toBe(29.00);
});

test('webhook processes invoice.payment_failed', function () {
    $eventId = 'evt_test_invoice_failed';
    $payload = [
        'id' => $eventId,
        'type' => 'invoice.payment_failed',
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

    // Verify billing status updated
    $this->tenant->refresh();
    expect($this->tenant->billing_status)->toBe('past_due');

    // Verify billing event logged
    $billingEvent = TenantBillingEvent::where('tenant_id', $this->tenant->id)
        ->where('event_type', 'invoice.payment_failed')
        ->first();

    expect($billingEvent)->not->BeNull();
});

test('webhook processes customer.subscription.created', function () {
    $eventId = 'evt_test_sub_created';
    $payload = [
        'id' => $eventId,
        'type' => 'customer.subscription.created',
        'data' => [
            'object' => [
                'customer' => $this->tenant->stripe_id,
                'status' => 'trialing',
                'trial_end' => time() + 14 * 24 * 60 * 60,
                'current_period_end' => time() + 30 * 24 * 60 * 60,
            ],
        ],
    ];

    $response = $this->postJson('/stripe/webhook', $payload);
    $response->assertStatus(200);

    // Process the queued job
    $this->artisan('queue:work', ['--once' => true]);

    // Verify billing status updated
    $this->tenant->refresh();
    expect($this->tenant->billing_status)->toBe('trialing');
    expect($this->tenant->trial_ends_at)->not->toBeNull();
    expect($this->tenant->subscription_ends_at)->not->toBeNull();

    // Verify billing event logged
    $billingEvent = TenantBillingEvent::where('tenant_id', $this->tenant->id)
        ->where('event_type', 'customer.subscription.created')
        ->first();

    expect($billingEvent)->not->BeNull();
});

test('webhook processes customer.subscription.deleted', function () {
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

    // Verify billing status updated
    $this->tenant->refresh();
    expect($this->tenant->billing_status)->toBe('cancelled');
    expect($this->tenant->subscription_ends_at)->not->BeNull();

    // Verify billing event logged
    $billingEvent = TenantBillingEvent::where('tenant_id', $this->tenant->id)
        ->where('event_type', 'customer.subscription.deleted')
        ->first();

    expect($billingEvent)->not->BeNull();
});

test('webhook processes customer.subscription.updated', function () {
    $eventId = 'evt_test_sub_updated';
    $payload = [
        'id' => $eventId,
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'customer' => $this->tenant->stripe_id,
                'cancel_at_period_end' => true,
                'current_period_end' => time() + 30 * 24 * 60 * 60,
            ],
        ],
    ];

    $response = $this->postJson('/stripe/webhook', $payload);
    $response->assertStatus(200);

    // Process the queued job
    $this->artisan('queue:work', ['--once' => true]);

    // Verify billing status updated
    $this->tenant->refresh();
    expect($this->tenant->billing_status)->toBe('cancelling');

    // Verify billing event logged
    $billingEvent = TenantBillingEvent::where('tenant_id', $this->tenant->id)
        ->where('event_type', 'customer.subscription.updated')
        ->first();

    expect($billingEvent)->not->BeNull();
});

test('webhook handles unknown event types gracefully', function () {
    $eventId = 'evt_test_unknown';
    $payload = [
        'id' => $eventId,
        'type' => 'unknown.event.type',
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

    // Verify webhook log was marked as processed
    $log = StripeWebhookLog::where('event_id', $eventId)->first();
    expect($log->status)->toBe('completed');
});

test('webhook job retries on failure', function () {
    $this->markTestSkipped('Requires mocking Stripe API failures');
});

test('webhook creates audit trail for all events', function () {
    $events = [
        'invoice.payment_succeeded',
        'invoice.payment_failed',
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
    ];

    foreach ($events as $index => $eventType) {
        $payload = [
            'id' => "evt_test_{$index}",
            'type' => $eventType,
            'data' => [
                'object' => [
                    'customer' => $this->tenant->stripe_id,
                ],
            ],
        ];

        $this->postJson('/stripe/webhook', $payload);
        $this->artisan('queue:work', ['--once' => true]);
    }

    // Verify all events were logged
    $eventCount = TenantBillingEvent::where('tenant_id', $this->tenant->id)->count();
    expect($eventCount)->toBe(count($events));
});

test('webhook maintains central context', function () {
    tenancy()->initialize($this->tenant);

    $payload = [
        'id' => 'evt_test_context',
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
    $log = StripeWebhookLog::where('event_id', 'evt_test_context')->first();
    expect($log)->not->BeNull();

    // Verify billing event is in central database
    $billingEvent = TenantBillingEvent::where('event_id', 'evt_test_context')->first();
    expect($billingEvent)->not->BeNull();
});