<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\StripeWebhookLog;
use App\Models\Tenant;
use App\Models\TenantBillingEvent;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Tenancy;

class HandleStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds to wait before retrying.
     */
    public $backoff = [10, 30, 60];

    /**
     * The webhook log ID.
     */
    protected int $webhookLogId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $webhookLogId)
    {
        $this->webhookLogId = $webhookLogId;
    }

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhookLog = StripeWebhookLog::find($this->webhookLogId);

        if (!$webhookLog) {
            Log::error("Webhook log not found: {$this->webhookLogId}");
            return;
        }

        // Mark as processing
        $webhookLog->markAsProcessing();

        try {
            // Force central context
            tenancy()->end();

            $payload = $webhookLog->payload;
            $type = $webhookLog->type;

            // Process the webhook event with retry logic
            $this->processWithRetry($webhookLog, $payload, $type);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Stripe API errors - should retry
            Log::error("Stripe API error in webhook processing", [
                'webhook_log_id' => $this->webhookLogId,
                'error' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode(),
            ]);

            $webhookLog->markAsFailed("Stripe API error: {$e->getMessage()}");

            // Don't throw - let the job retry naturally
            $this->release(30); // Release for retry after 30 seconds

        } catch (\Exception $e) {
            // Other errors
            Log::error("Webhook processing failed", [
                'webhook_log_id' => $this->webhookLogId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $webhookLog->markAsFailed($e->getMessage());

            // Send alert for critical failures
            if ($this->attempts() >= 2) {
                app(MonitoringAlertService::class)->alertWebhookFailure($webhookLog, $e->getMessage());
            }

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Process webhook with retry logic.
     */
    protected function processWithRetry(StripeWebhookLog $webhookLog, array $payload, string $type): void
    {
        $maxRetries = 3;
        $attempt = 0;
        $lastError = null;

        while ($attempt < $maxRetries) {
            try {
                $controller = app(StripeWebhookController::class);
                $method = $this->getEventMethod($type);

                if (method_exists($controller, $method)) {
                    $controller->$method($payload);

                    // Log billing event
                    $this->logBillingEvent($webhookLog, $payload);

                    // Mark as completed
                    $webhookLog->markAsProcessed();

                    Log::info("Webhook processed successfully: {$type}");
                    return;
                } else {
                    Log::warning("No handler for webhook type: {$type}");
                    $webhookLog->markAsProcessed(); // Still mark as processed to avoid retries
                    return;
                }

            } catch (\Exception $e) {
                $attempt++;
                $lastError = $e;

                if ($attempt < $maxRetries) {
                    // Exponential backoff
                    $delay = min(30, 5 * pow(2, $attempt - 1));
                    sleep($delay);
                } else {
                    throw $e;
                }
            }
        }

        throw new \Exception("Webhook processing failed after {$maxRetries} attempts: {$lastError->getMessage()}");
    }

    /**
     * Get the event method name from event type.
     */
    protected function getEventMethod(string $type): string
    {
        return 'handle' . str_replace('.', '', ucwords($type, '.'));
    }

    /**
     * Log billing event for audit trail.
     */
    protected function logBillingEvent(StripeWebhookLog $webhookLog, array $payload): void
    {
        $tenantId = $webhookLog->tenant_id;
        $type = $webhookLog->type;

        if (!$tenantId) {
            return;
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return;
        }

        $oldStatus = $tenant->billing_status;
        $newStatus = $tenant->billing_status;
        $amount = null;
        $description = null;

        // Extract billing information based on event type
        if (str_contains($type, 'invoice.payment')) {
            $invoiceData = $payload['data']['object'] ?? [];
            $amount = ($invoiceData['amount_paid'] ?? 0) / 100;
            $description = 'Invoice payment';
        } elseif (str_contains($type, 'subscription')) {
            $description = 'Subscription change';
        }

        TenantBillingEvent::log(
            tenantId: $tenantId,
            eventType: $type,
            oldStatus: $oldStatus,
            newStatus: $newStatus,
            payload: $payload,
            amount: $amount,
            description: $description,
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Webhook job failed permanently", [
            'webhook_log_id' => $this->webhookLogId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
