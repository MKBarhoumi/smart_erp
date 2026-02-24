<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'version', 'controlling_agency',
        'sender_identifier', 'sender_type',
        'receiver_identifier', 'receiver_type',
        'document_identifier', 'document_type_code', 'document_type_name',
        'dates', 'payment_section', 'free_texts', 'special_conditions',
        'loc_section', 'invoice_amounts', 'invoice_allowances',
        'ref_ttn_id', 'ref_ttn_value', 'ref_cev', 'ref_ttn_dates',
        'signatures', 'status',
    ];

    protected $casts = [
        'dates'               => 'array',
        'payment_section'     => 'array',
        'free_texts'          => 'array',
        'special_conditions'  => 'array',
        'loc_section'         => 'array',
        'invoice_amounts'     => 'array',
        'invoice_allowances'  => 'array',
        'ref_ttn_dates'       => 'array',
        'signatures'          => 'array',
    ];

    public function partners(): HasMany
    {
        return $this->hasMany(InvoicePartner::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->whereNull('parent_line_id');
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(InvoiceTax::class);
    }
}