<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentTypeCode;
use App\Enums\OldInvoiceStatus;
use App\Exceptions\OldInvoiceStateException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class OldInvoice extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'oldinvoices';

    protected $fillable = [
        'customer_id',
        'created_by',
        'parent_oldinvoice_id',
        'oldinvoice_number',
        'document_identifier',
        'document_type_code',
        'status',
        'oldinvoice_date',
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
            'status' => OldInvoiceStatus::class,
            'oldinvoice_date' => 'date',
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
     * @return HasMany<OldInvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OldInvoiceLine::class, 'oldinvoice_id')->orderBy('line_number');
    }

    /**
     * @return HasMany<OldInvoiceTaxLine, $this>
     */
    public function taxLines(): HasMany
    {
        return $this->hasMany(OldInvoiceTaxLine::class, 'oldinvoice_id');
    }

    /**
     * @return HasMany<OldInvoiceAllowance, $this>
     */
    public function allowances(): HasMany
    {
        return $this->hasMany(OldInvoiceAllowance::class, 'oldinvoice_id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'oldinvoice_id');
    }

    /**
     * @return HasMany<TTNSubmissionLog, $this>
     */
    public function ttnLogs(): HasMany
    {
        return $this->hasMany(TTNSubmissionLog::class, 'oldinvoice_id');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parentOldInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_oldinvoice_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @throws OldInvoiceStateException
     */
    public function transitionTo(OldInvoiceStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new OldInvoiceStateException(
                "Cannot transition from {$this->status->value} to {$newStatus->value}"
            );
        }

        $this->status = $newStatus;
    }

    public function isEditable(): bool
    {
        return $this->status === OldInvoiceStatus::DRAFT;
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
