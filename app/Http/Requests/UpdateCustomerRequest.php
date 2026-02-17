<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\IdentifierType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'identifier_type' => ['required', Rule::enum(IdentifierType::class)],
            'identifier_value' => ['required', 'string', 'max:50'],
            'matricule_fiscal' => ['nullable', 'string', 'max:20'],
            'category_type' => ['nullable', 'string', 'size:1', 'in:A,B,D,N,P'],
            'person_type' => ['nullable', 'string', 'size:1', 'in:C,M,N,P'],
            'tax_office' => ['nullable', 'string', 'size:3'],
            'registre_commerce' => ['nullable', 'string', 'max:50'],
            'legal_form' => ['nullable', 'string', 'max:20'],
            'address_description' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
