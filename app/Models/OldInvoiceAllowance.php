<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OldInvoiceAllowance extends Model
{
    use HasUuids;

    public $timestamps = false;
    
    /**
     * @var string
     */
    protected $table = 'oldinvoice_allowances';


    protected $fillable = [
        'oldinvoice_id',
        'type',
        'reason',
        'rate',
        'amount',
    ];

    /**
     * @return BelongsTo<OldInvoice, $this>
     */
    public function oldinvoice(): BelongsTo
    {
        return $this->belongsTo(OldInvoice::class);
    }
}
