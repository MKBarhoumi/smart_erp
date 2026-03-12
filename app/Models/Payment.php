<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;
    use LogsActivity;

    protected $fillable = [
        'oldinvoice_id',
        'invoice_id',
        'created_by',
        'payment_date',
        'amount',
        'method',
        'reference',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<OldInvoice, $this>
     */
    public function oldinvoice(): BelongsTo
    {
        return $this->belongsTo(OldInvoice::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
