<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceLine extends Model
{
    use HasUuids;

    protected $fillable = [
        'invoice_id',
        'parent_line_id',
        'product_id',
        'line_number',
        'item_code',
        'item_description',
        'item_lang',
        'quantity',
        'unit_of_measure',
        'unit_price',
        'discount_rate',
        'discount_amount',
        'line_net_amount',
        'tva_rate',
        'tva_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:3',
            'discount_rate' => 'decimal:2',
            'discount_amount' => 'decimal:3',
            'line_net_amount' => 'decimal:3',
            'tva_rate' => 'decimal:2',
            'tva_amount' => 'decimal:3',
            'line_total' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<self, $this>
     */
    public function subLines(): HasMany
    {
        return $this->hasMany(self::class, 'parent_line_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parentLine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_line_id');
    }
}
