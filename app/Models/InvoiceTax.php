<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceTax extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id',
        'tax_type_code',
        'tax_type_name',
        'tax_category',
        'tax_rate',
        'tax_rate_basis',
        'amounts',
    ];

    protected $casts = [
        'amounts' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}