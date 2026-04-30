class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle incoming webhook.
     */
    public function handleWebhook(Request $request)
    {
        // 🔥 CRITICAL: Force central context for all billing operations
        tenancy()->end();

        $eventId = $request->input('id');
        $eventType = $request->input('type');

        // 🔥 CRITICAL: Idempotency check - prevent duplicate processing
        if (StripeWebhookLog::isEventProcessed($eventId)) {
            return response()->json([
                'status' => 'already_processed',
                'event_id' => $eventId,
            ]);
        }

        // Create webhook log entry
        $webhookLog = StripeWebhookLog::create([
            'event_id' => $eventId,
            'type' => $eventType,
            'payload' => $request->all(),
            'status' => 'pending',
        ]);

        // Extract tenant ID if available
        $customerId = $request->input('data.object.customer');
        if ($customerId) {
            $tenant = Tenant::where('stripe_id', $customerId)->first();
            if ($tenant) {
                $webhookLog->tenant_id = $tenant->id;
                $webhookLog->save();
            }
        }

        // 🔥 CRITICAL: Queue webhook processing for scalability
        // Return immediately to prevent Stripe timeout
        try {
            dispatch(new \App\Jobs\HandleStripeWebhook($webhookLog->id))
                ->onQueue('stripe')
                ->delay(now()->addSeconds(5)); // Small delay to ensure DB consistency
        } catch (\Exception $e) {
            Log::error("Failed to dispatch webhook job", [
                'webhook_log_id' => $webhookLog->id,
                'error' => $e->getMessage(),
            ]);

            // Still return success to Stripe to prevent retries
            // The webhook will be processed manually later
        }

        // 🔥 CRITICAL: Return immediately to prevent Stripe timeout
        // Stripe expects response within 10 seconds
        return response()->json([
            'status' => 'queued',
            'event_id' => $eventId,
            'webhook_log_id' => $webhookLog->id,
        ]);
    }

    /**
     * Handle customer subscription created.
     */
    public function handleCustomerSubscriptionCreated(array $payload): void
    {
        parent::handleCustomerSubscriptionCreated($payload);

        $stripeId = $payload['data']['object']['customer'];
        $tenant = Tenant::where('stripe_id', $stripeId)->first();

        if ($tenant) {
            $subscriptionData = $payload['data']['object'];

            $tenant->update([
                'data->status' => 'active',
                'billing_status' => $subscriptionData['status'] ?? 'trialing',
                'trial_ends_at' => isset($subscriptionData['trial_end'])
                    ? \Carbon\Carbon::createFromTimestamp($subscriptionData['trial_end'])
                    : null,
                'subscription_ends_at' => isset($subscriptionData['current_period_end'])
                    ? \Carbon\Carbon::createFromTimestamp($subscriptionData['current_period_end'])
                    : null,
            ]);

            Log::info("Subscription created for tenant: {$tenant->id}");
        }
    }

    /**
     * Handle customer subscription updated.
     */
    public function handleCustomerSubscriptionUpdated(array $payload): void
    {
        parent::handleCustomerSubscriptionUpdated($payload);

        $stripeId = $payload['data']['object']['customer'];
        $tenant = Tenant::where('stripe_id', $stripeId)->first();

        if ($tenant) {
            $subscriptionData = $payload['data']['object'];

            $billingStatus = 'active';

            if (isset($subscriptionData['cancel_at_period_end']) && $subscriptionData['cancel_at_period_end']) {
                $billingStatus = 'cancelling';
            }

            $tenant->update([
                'data->status' => 'active',
                'billing_status' => $billingStatus,
                'subscription_ends_at' => isset($subscriptionData['current_period_end'])
                    ? \Carbon\Carbon::createFromTimestamp($subscriptionData['current_period_end'])
                    : null,
            ]);

            Log::info("Subscription updated for tenant: {$tenant->id}");
        }
    }

    /**
     * Handle customer subscription deleted.
     */
    public function handleCustomerSubscriptionDeleted(array $payload): void
    {
        parent::handleCustomerSubscriptionDeleted($payload);

        $stripeId = $payload['data']['object']['customer'];
        $tenant = Tenant::where('stripe_id', $stripeId)->first();

        if ($tenant) {
            $tenant->update([
                'data->status' => 'suspended',
                'billing_status' => 'cancelled',
                'subscription_ends_at' => now(),
            ]);

            Log::info("Subscription deleted for tenant: {$tenant->id}");
        }
    }

    /**
     * Handle invoice payment succeeded.
     */
    public function handleInvoicePaymentSucceeded(array $payload): void
    {
        parent::handleInvoicePaymentSucceeded($payload);

        $stripeId = $payload['data']['object']['customer'];
        $tenant = Tenant::where('stripe_id', $stripeId)->first();

        if ($tenant) {
            $invoiceData = $payload['data']['object'];

            $tenant->update([
                'data->status' => 'active',
                'billing_status' => 'active',
                'last_payment_at' => now(),
                'next_payment_at' => isset($invoiceData['next_payment_attempt'])
                    ? \Carbon\Carbon::createFromTimestamp($invoiceData['next_payment_attempt'])
                    : null,
            ]);

            Log::info("Payment succeeded for tenant: {$tenant->id}");
        }
    }

    /**
     * Handle invoice payment failed.
     */
    public function handleInvoicePaymentFailed(array $payload): void
    {
        parent::handleInvoicePaymentFailed($payload);

        $stripeId = $payload['data']['object']['customer'];
        $tenant = Tenant::where('stripe_id', $stripeId)->first();

        if ($tenant) {
            $tenant->update([
                'data->status' => 'past_due',
                'billing_status' => 'past_due',
            ]);

            Log::warning("Payment failed for tenant: {$tenant->id}");
        }
    }