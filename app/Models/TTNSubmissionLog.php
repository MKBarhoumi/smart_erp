<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TTNSubmissionLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'ttn_submission_logs';

    protected $fillable = [
        'invoice_id',
        'attempt_number',
        'request_xml',
        'response_xml',
        'response_status',
        'error_code',
        'error_message',
        'submitted_at',
        'responded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
