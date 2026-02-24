<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OldInvoiceTaxLine extends Model
{
    use HasUuids;
    
    /**
     * @var string
     */
    protected $table = 'oldinvoice_tax_lines';
    

    public $timestamps = false;

    protected $fillable = [
        'oldinvoice_id',
        'tax_type_code',
        'tax_type_name',
        'tax_rate',
        'taxable_amount',
        'tax_amount',
    ];

    /**
     * @return BelongsTo<OldInvoice, $this>
     */
    public function oldinvoice(): BelongsTo
    {
        return $this->belongsTo(OldInvoice::class);
    }
}
