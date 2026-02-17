<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IdentifierType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'identifier_type',
        'identifier_value',
        'name',
        'address_description',
        'street',
        'city',
        'postal_code',
        'country_code',
        'matricule_fiscal',
        'category_type',
        'person_type',
        'tax_office',
        'registre_commerce',
        'legal_form',
        'phone',
        'fax',
        'email',
        'website',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'identifier_type' => IdentifierType::class,
        ];
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
