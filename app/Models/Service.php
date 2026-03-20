<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'unit',
        'unit_price',
        'tax_rate',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'unit_price' => 'decimal:3',
            'tax_rate' => 'decimal:2',
        ];
    }

    /**
     * Get service categories as options.
     * @return array<int, string>
     */
    public static function categories(): array
    {
        return ['IT', 'Consulting', 'Maintenance', 'Training', 'Other'];
    }

    /**
     * Get billing units as options.
     * @return array<int, string>
     */
    public static function billingUnits(): array
    {
        return ['Hour', 'Day', 'Unit', 'Forfait'];
    }

    /**
     * Get invoices where this service was used.
     * @return HasMany<OldInvoiceLine, $this>
     */
    public function invoiceLines(): HasMany
    {
        return $this->hasMany(OldInvoiceLine::class, 'product_id');
    }
}
