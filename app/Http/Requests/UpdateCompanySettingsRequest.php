<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'matricule_fiscal' => ['required', 'string', 'max:20'],
            'category_type' => ['required', 'string', 'size:1', 'in:A,B,D,N,P'],
            'person_type' => ['required', 'string', 'size:1', 'in:C,M,N,P'],
            'tax_office' => ['nullable', 'string', 'size:3'],
            'registre_commerce' => ['nullable', 'string', 'max:50'],
            'legal_form' => ['nullable', 'string', 'max:20'],
            'address_description' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'bank_rib' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_branch_code' => ['nullable', 'string', 'max:10'],
            'postal_account' => ['nullable', 'string', 'max:30'],
            'oldinvoice_prefix' => ['nullable', 'string', 'max:10'],
            'oldinvoice_number_format' => ['nullable', 'string', 'max:50'],
            'default_timbre_fiscal' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
