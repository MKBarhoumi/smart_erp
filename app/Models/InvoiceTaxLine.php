<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceTaxLine extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'tax_type_code',
        'tax_type_name',
        'tax_rate',
        'taxable_amount',
        'tax_amount',
    ];

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
