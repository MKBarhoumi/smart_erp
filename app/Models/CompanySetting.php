<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
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
        'iban',
        'bank_rib',
        'bank_name',
        'bank_branch_code',
        'postal_account',
        'oldinvoice_prefix',
        'oldinvoice_number_format',
        'next_oldinvoice_counter',
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
            'next_oldinvoice_counter' => 'integer',
            'certificate_expires_at' => 'datetime',
            'certificate_passphrase' => 'encrypted',
            'default_timbre_fiscal' => 'decimal:3',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function resolveForUser(?User $user): ?self
    {
        if (!$user) {
            return static::query()->whereNull('user_id')->first();
        }

        return static::query()->where('user_id', $user->id)->first()
            ?? static::query()->whereNull('user_id')->first();
    }

    /**
     * @return array{identifier: string, name: string, street: string, city: string, postal_code: string, country: string}
     */
    public static function senderDefaultsForUser(?User $user): array
    {
        $settings = static::resolveForUser($user);

        return [
            'identifier' => $settings?->matricule_fiscal ?? '',
            'name' => $settings?->company_name ?? '',
            'street' => $settings?->street ?? '',
            'city' => $settings?->city ?? '',
            'postal_code' => $settings?->postal_code ?? '',
            'country' => $settings?->country_code ?? '',
        ];
    }

    /**
     * Accessor for matricule_fiscale (alias for matricule_fiscal).
     */
    public function getMatriculeFiscaleAttribute(): ?string
    {
        return $this->matricule_fiscal;
    }

    /**
     * Accessor for address_street (alias for street).
     */
    public function getAddressStreetAttribute(): ?string
    {
        return $this->street;
    }

    /**
     * Accessor for address_postal_code (alias for postal_code).
     */
    public function getAddressPostalCodeAttribute(): ?string
    {
        return $this->postal_code;
    }

    /**
     * Accessor for address_city (alias for city).
     */
    public function getAddressCityAttribute(): ?string
    {
        return $this->city;
    }
}
