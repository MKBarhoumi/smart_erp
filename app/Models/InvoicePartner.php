<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoicePartner extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_id', 'function_code',
        'partner_identifier', 'partner_identifier_type',
        'partner_name', 'partner_name_type',
        'address_description', 'street', 'city', 'postal_code',
        'country', 'country_code_list', 'address_lang',
        'locations', 'references', 'contacts',
    ];

    protected $casts = [
        'locations'  => 'array',
        'references' => 'array',
        'contacts'   => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}