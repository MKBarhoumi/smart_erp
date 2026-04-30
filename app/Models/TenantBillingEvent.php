<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBillingEvent extends Model
{
    protected $fillable = [
        'tenant_id',
        'event_type',
        'old_status',
        'new_status',
        'payload',
        'amount',
        'currency',
        'description',
        'stripe_event_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the tenant that owns the billing event.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Create a billing event log.
     */
    public static function log(string $tenantId, string $eventType, ?string $oldStatus = null, ?string $newStatus = null, ?array $payload = null, ?float $amount = null, ?string $description = null): self
    {
        return self::create([
            'tenant_id' => $tenantId,
            'event_type' => $eventType,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'payload' => $payload,
            'amount' => $amount,
            'currency' => 'USD',
            'description' => $description,
        ]);
    }

    /**
     * Scope a query to only include payment events.
     */
    public function scopePayments($query)
    {
        return $query->where('event_type', 'like', '%payment%');
    }

    /**
     * Scope a query to only include subscription events.
     */
    public function scopeSubscriptions($query)
    {
        return $query->where('event_type', 'like', '%subscription%');
    }

    /**
     * Scope a query for a specific tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query for a specific date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        if ($this->amount === null) {
            return 'N/A';
        }

        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Check if this is a payment event.
     */
    public function isPayment(): bool
    {
        return str_contains($this->event_type, 'payment');
    }

    /**
     * Check if this is a subscription event.
     */
    public function isSubscription(): bool
    {
        return str_contains($this->event_type, 'subscription');
    }

    /**
     * Check if this is a successful event.
     */
    public function isSuccessful(): bool
    {
        return str_contains($this->event_type, 'succeeded') || str_contains($this->event_type, 'created');
    }

    /**
     * Check if this is a failed event.
     */
    public function isFailed(): bool
    {
        return str_contains($this->event_type, 'failed') || str_contains($this->event_type, 'deleted');
    }
}
