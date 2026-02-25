<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Exceptions\InvoiceStateException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'signatures', 'status', 'signed_xml', 'submitted_at', 'accepted_at',
        'rejection_reason', 'notes', 'created_by',
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
        'submitted_at'        => 'datetime',
        'accepted_at'         => 'datetime',
    ];

    public function partners(): HasMany
    {
        return $this->hasMany(InvoicePartner::class);
    }

    public function senderPartner(): HasMany
    {
        return $this->hasMany(InvoicePartner::class)->where('function_code', 'I-62');
    }

    public function receiverPartner(): HasMany
    {
        return $this->hasMany(InvoicePartner::class)->where('function_code', 'I-64');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->whereNull('parent_line_id');
    }

    public function allLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(InvoiceTax::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if invoice is editable (draft or rejected state)
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [InvoiceStatus::DRAFT->value, InvoiceStatus::REJECTED->value]);
    }

    /**
     * Check if invoice can be deleted
     */
    public function isDeletable(): bool
    {
        return $this->status === InvoiceStatus::DRAFT->value;
    }

    /**
     * Transition invoice to a new status
     */
    public function transitionTo(InvoiceStatus $target): self
    {
        $current = InvoiceStatus::from($this->status);

        if (!$current->canTransitionTo($target)) {
            throw new InvoiceStateException(
                "Cannot transition from {$current->label()} to {$target->label()}"
            );
        }

        $this->update(['status' => $target->value]);

        return $this;
    }

    /**
     * Get display-friendly invoice number
     */
    public function getDisplayNumberAttribute(): string
    {
        return $this->document_identifier ?? "INV-{$this->id}";
    }

    /**
     * Calculate total HT from invoice_amounts JSON
     */
    public function getTotalHtAttribute(): string
    {
        $amounts = $this->invoice_amounts ?? [];
        foreach ($amounts as $amount) {
            if (($amount['amount_type_code'] ?? '') === 'I-176') {
                return $amount['amount'] ?? '0.000';
            }
        }
        return '0.000';
    }

    /**
     * Calculate total TVA from invoice_amounts JSON
     */
    public function getTotalTvaAttribute(): string
    {
        $amounts = $this->invoice_amounts ?? [];
        foreach ($amounts as $amount) {
            if (($amount['amount_type_code'] ?? '') === 'I-181') {
                return $amount['amount'] ?? '0.000';
            }
        }
        return '0.000';
    }

    /**
     * Calculate total TTC from invoice_amounts JSON
     */
    public function getTotalTtcAttribute(): string
    {
        $amounts = $this->invoice_amounts ?? [];
        foreach ($amounts as $amount) {
            if (($amount['amount_type_code'] ?? '') === 'I-180') {
                return $amount['amount'] ?? '0.000';
            }
        }
        return '0.000';
    }

    /**
     * Get invoice date from dates JSON
     */
    public function getInvoiceDateAttribute(): ?string
    {
        $dates = $this->dates ?? [];
        foreach ($dates as $date) {
            if (($date['function_code'] ?? '') === 'I-31') {
                // Convert from ddMMyy format to Y-m-d
                $value = $date['value'] ?? '';
                if (strlen($value) === 6) {
                    $day = substr($value, 0, 2);
                    $month = substr($value, 2, 2);
                    $year = '20' . substr($value, 4, 2);
                    return "{$year}-{$month}-{$day}";
                }
                return $value;
            }
        }
        return null;
    }
}