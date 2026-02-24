<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceLine extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id', 'parent_line_id',
        'item_identifier', 'item_code', 'item_description', 'item_lang',
        'api_details', 'quantity', 'measurement_unit',
        'dates', 'tax_type_code', 'tax_type_name', 'tax_category',
        'tax_rate', 'tax_rate_basis', 'allowances', 'amounts',
        'free_texts', 'sort_order',
    ];

    protected $casts = [
        'api_details' => 'array',
        'dates'       => 'array',
        'allowances'  => 'array',
        'amounts'     => 'array',
        'free_texts'  => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function subLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class, 'parent_line_id');
    }

    public function parentLine(): BelongsTo
    {
        return $this->belongsTo(InvoiceLine::class, 'parent_line_id');
    }
}