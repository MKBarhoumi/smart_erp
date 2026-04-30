<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\DatabaseConfig;
use Laravel\Cashier\Billable;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasUuids, Billable;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'central';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'matricule_fiscal',
        'database_name',
        'plan_id',
        'status',
        'trial_ends_at',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'billing_status',
        'subscription_ends_at',
        'last_payment_at',
        'next_payment_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the domains for the tenant.
     *
     * @return HasMany<Domain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Get the plan for the tenant.
     *
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the primary domain for the tenant.
     */
    public function primaryDomain(): ?Domain
    {
        return $this->domains()->where('is_primary', true)->first();
    }

    /**
     * Scope a query to only include active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('data->status', 'active');
    }

    /**
     * Scope a query to only include trial tenants.
     */
    public function scopeTrial($query)
    {
        return $query->where('data->status', 'trial');
    }

    /**
     * Scope a query to only include suspended tenants.
     */
    public function scopeSuspended($query)
    {
        return $query->where('data->status', 'suspended');
    }

    /**
     * Scope a query to only include cancelled tenants.
     */
    public function scopeCancelled($query)
    {
        return $query->where('data->status', 'cancelled');
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tenant is in trial.
     */
    public function isTrial(): bool
    {
        return $this->status === 'trial';
    }

    /**
     * Check if tenant is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if tenant is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if trial has expired.
     */
    public function isTrialExpired(): bool
    {
        return $this->isTrial() && $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Get the database configuration for the tenant.
     *
     * @return DatabaseConfig
     */
    public function database(): DatabaseConfig
    {
        return new DatabaseConfig($this);
    }

    /**
     * Check if tenant has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscribed('default') || $this->onTrial();
    }

    /**
     * Check if tenant can access a feature based on plan limits.
     */
    public function canAccessFeature(string $feature, int $currentCount = 0): bool
    {
        if (!$this->plan) {
            return false;
        }

        $limit = $this->plan->getLimit($feature);

        if ($limit === null) {
            return true;
        }

        return $currentCount < $limit;
    }

    /**
     * Get the current plan for the tenant.
     */
    public function getCurrentPlan(): ?Plan
    {
        return $this->plan;
    }

    /**
     * Check if tenant is on a specific plan.
     */
    public function isOnPlan(string $planSlug): bool
    {
        return $this->plan && $this->plan->slug === $planSlug;
    }

    /**
     * Upgrade tenant to a new plan.
     */
    public function upgradePlan(Plan $newPlan): bool
    {
        if ($this->plan_id === $newPlan->id) {
            return false;
        }

        $this->plan_id = $newPlan->id;
        return $this->save();
    }

    /**
     * Get billing status.
     */
    public function getBillingStatus(): string
    {
        return $this->billing_status ?? 'trial';
    }

    /**
     * Check if tenant is in trial period.
     */
    public function isInTrial(): bool
    {
        return $this->billing_status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant is active (paid subscription).
     */
    public function isBillingActive(): bool
    {
        return $this->billing_status === 'active';
    }

    /**
     * Check if tenant is past due.
     */
    public function isPastDue(): bool
    {
        return $this->billing_status === 'past_due';
    }

    /**
     * Check if tenant is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->billing_status === 'cancelled';
    }

    /**
     * Update billing status.
     */
    public function updateBillingStatus(string $status): void
    {
        $this->billing_status = $status;
        $this->save();
    }

    /**
     * Check if tenant can access the system.
     */
    public function canAccessSystem(): bool
    {
        return $this->isInTrial() ||
               $this->isBillingActive() ||
               $this->onGracePeriod();
    }

    /**
     * Get days remaining in trial.
     */
    public function getTrialDaysRemaining(): ?int
    {
        if (!$this->isInTrial()) {
            return null;
        }

        return $this->trial_ends_at->diffInDays(now());
    }

    /**
     * Get days until next payment.
     */
    public function getDaysUntilNextPayment(): ?int
    {
        if (!$this->next_payment_at) {
            return null;
        }

        return $this->next_payment_at->diffInDays(now());
    }
}
