<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use HasUuids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'item_lang',
        'unit_of_measure',
        'unit_price',
        'tva_rate',
        'discount_rate',
        'is_subject_to_timbre',
        'track_inventory',
        'min_stock_alert',
        'current_stock',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'track_inventory' => 'boolean',
            'is_active' => 'boolean',
            'unit_price' => 'decimal:3',
            'tva_rate' => 'decimal:3',
            'discount_rate' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return HasMany<OldInvoiceLine, $this>
     */
    public function oldinvoiceLines(): HasMany
    {
        return $this->hasMany(OldInvoiceLine::class);
    }
}
