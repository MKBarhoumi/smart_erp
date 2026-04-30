<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TenantUsage extends Model
{
    protected $fillable = [
        'tenant_id',
        'metric',
        'value',
        'period',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    /**
     * Get the tenant that owns the usage record.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Record usage for a metric.
     */
    public static function recordUsage(string $tenantId, string $metric, int $value, string $period = 'monthly'): self
    {
        $now = Carbon::now();
        $periodStart = $period === 'monthly' ? $now->copy()->startOfMonth() : $now->copy()->startOfYear();
        $periodEnd = $period === 'monthly' ? $now->copy()->endOfMonth() : $now->copy()->endOfYear();

        return self::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'metric' => $metric,
                'period' => $period,
                'period_start' => $periodStart,
            ],
            [
                'value' => $value,
                'period_end' => $periodEnd,
            ]
        );
    }

    /**
     * Increment usage for a metric.
     */
    public static function incrementUsage(string $tenantId, string $metric, int $amount = 1, string $period = 'monthly'): self
    {
        $now = Carbon::now();
        $periodStart = $period === 'monthly' ? $now->copy()->startOfMonth() : $now->copy()->startOfYear();
        $periodEnd = $period === 'monthly' ? $now->copy()->endOfMonth() : $now->copy()->endOfYear();

        $usage = self::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'metric' => $metric,
                'period' => $period,
                'period_start' => $periodStart,
            ],
            [
                'value' => 0,
                'period_end' => $periodEnd,
            ]
        );

        $usage->increment('value');

        return $usage;
    }

    /**
     * Get usage for a specific metric and period.
     */
    public static function getUsage(string $tenantId, string $metric, string $period = 'monthly'): ?self
    {
        $now = Carbon::now();
        $periodStart = $period === 'monthly' ? $now->copy()->startOfMonth() : $now->copy()->startOfYear();

        return self::where('tenant_id', $tenantId)
            ->where('metric', $metric)
            ->where('period', $period)
            ->where('period_start', $periodStart)
            ->first();
    }

    /**
     * Get all metrics for a tenant in current period.
     */
    public static function getAllMetricsForTenant(string $tenantId, string $period = 'monthly'): array
    {
        $now = Carbon::now();
        $periodStart = $period === 'monthly' ? $now->copy()->startOfMonth() : $now->copy()->startOfYear();

        return self::where('tenant_id', $tenantId)
            ->where('period', $period)
            ->where('period_start', $periodStart)
            ->get()
            ->pluck('value', 'metric')
            ->toArray();
    }

    /**
     * Check if usage is approaching limit.
     */
    public function isApproachingLimit(int $limit, float $threshold = 0.8): bool
    {
        if ($limit === null) {
            return false;
        }

        return ($this->value / $limit) >= $threshold;
    }

    /**
     * Get usage percentage.
     */
    public function getUsagePercentage(int $limit): float
    {
        if ($limit === null || $limit === 0) {
            return 0;
        }

        return ($this->value / $limit) * 100;
    }

    /**
     * Get remaining capacity.
     */
    public function getRemaining(int $limit): int
    {
        if ($limit === null) {
            return PHP_INT_MAX;
        }

        return max(0, $limit - $this->value);
    }

    /**
     * Scope a query for a specific tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query for a specific metric.
     */
    public function scopeForMetric($query, string $metric)
    {
        return $query->where('metric', $metric);
    }

    /**
     * Scope a query for current period.
     */
    public function scopeCurrentPeriod($query, string $period = 'monthly')
    {
        $now = Carbon::now();
        $periodStart = $period === 'monthly' ? $now->copy()->startOfMonth() : $now->copy()->startOfYear();

        return $query->where('period', $period)
            ->where('period_start', $periodStart);
    }

    /**
     * Scope a query for a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
    }
}
