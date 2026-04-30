<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'stripe_price_id',
        'description',
        'price',
        'currency',
        'interval',
        'max_users',
        'max_customers',
        'max_products',
        'max_invoices',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'integer',
        'max_users' => 'integer',
        'max_customers' => 'integer',
        'max_products' => 'integer',
        'max_invoices' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price / 100, 2) . ' ' . $this->currency;
    }

    public function getIntervalDisplayAttribute(): string
    {
        return match($this->interval) {
            'month' => 'Monthly',
            'year' => 'Yearly',
            'week' => 'Weekly',
            default => ucfirst($this->interval),
        };
    }

    public function isFree(): bool
    {
        return $this->price === 0 || $this->stripe_price_id === null;
    }

    public function hasLimit(string $feature): bool
    {
        return !is_null($this->{"max_{$feature}"});
    }

    public function getLimit(string $feature): ?int
    {
        return $this->{"max_{$feature}"};
    }
}
