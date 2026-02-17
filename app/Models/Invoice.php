<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentTypeCode;
use App\Enums\InvoiceStatus;
use App\Exceptions\InvoiceStateException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'created_by',
        'parent_invoice_id',
        'invoice_number',
        'document_identifier',
        'document_type_code',
        'status',
        'invoice_date',
        'due_date',
        'billing_period_start',
        'billing_period_end',
        'total_gross',
        'total_discount',
        'total_net_before_disc',
        'total_ht',
        'total_tva',
        'timbre_fiscal',
        'total_ttc',
        'ref_ttn_val',
        'cev_qr_content',
        'signed_xml',
        'submitted_at',
        'accepted_at',
        'rejection_reason',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type_code' => DocumentTypeCode::class,
            'status' => InvoiceStatus::class,
            'invoice_date' => 'date',
            'due_date' => 'date',
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'submitted_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('line_number');
    }

    /**
     * @return HasMany<InvoiceTaxLine, $this>
     */
    public function taxLines(): HasMany
    {
        return $this->hasMany(InvoiceTaxLine::class);
    }

    /**
     * @return HasMany<InvoiceAllowance, $this>
     */
    public function allowances(): HasMany
    {
        return $this->hasMany(InvoiceAllowance::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<TTNSubmissionLog, $this>
     */
    public function ttnLogs(): HasMany
    {
        return $this->hasMany(TTNSubmissionLog::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parentInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_invoice_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @throws InvoiceStateException
     */
    public function transitionTo(InvoiceStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new InvoiceStateException(
                "Cannot transition from {$this->status->value} to {$newStatus->value}"
            );
        }

        $this->status = $newStatus;
    }

    public function isEditable(): bool
    {
        return $this->status === InvoiceStatus::DRAFT;
    }

    public function getPaidAmountAttribute(): string
    {
        return bcadd(
            (string) $this->payments()->sum('amount'),
            '0',
            3
        );
    }

    public function getRemainingBalanceAttribute(): string
    {
        return bcsub($this->total_ttc, $this->paid_amount, 3);
    }

    public function getPaymentStatusAttribute(): string
    {
        $remaining = $this->remaining_balance;
        if (bccomp($remaining, '0.000', 3) <= 0) {
            return 'paid';
        }
        if (bccomp($this->paid_amount, '0.000', 3) > 0) {
            return 'partial';
        }
        if ($this->due_date && $this->due_date->isPast()) {
            return 'overdue';
        }
        return 'unpaid';
    }
}
