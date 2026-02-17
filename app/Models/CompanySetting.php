<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_name',
        'matricule_fiscal',
        'category_type',
        'person_type',
        'tax_office',
        'registre_commerce',
        'legal_form',
        'address_description',
        'street',
        'city',
        'postal_code',
        'country_code',
        'phone',
        'fax',
        'email',
        'website',
        'logo_path',
        'bank_rib',
        'bank_name',
        'bank_branch_code',
        'postal_account',
        'invoice_prefix',
        'invoice_number_format',
        'next_invoice_counter',
        'default_timbre_fiscal',
        'certificate_file',
        'certificate_passphrase',
        'certificate_expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'next_invoice_counter' => 'integer',
            'certificate_expires_at' => 'datetime',
            'certificate_passphrase' => 'encrypted',
        ];
    }
}
