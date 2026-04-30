<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'matricule_fiscal' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:3'],
            'bank_name' => ['required', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'min:15', 'max:34'],
            'default_timbre_fiscal' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $iban = $this->input('iban');

        if (is_string($iban)) {
            $iban = strtoupper(preg_replace('/\s+/', '', $iban) ?? '');
        }

        $this->merge([
            'company_name' => is_string($this->input('company_name')) ? trim($this->input('company_name')) : $this->input('company_name'),
            'matricule_fiscal' => is_string($this->input('matricule_fiscal')) ? trim($this->input('matricule_fiscal')) : $this->input('matricule_fiscal'),
            'email' => is_string($this->input('email')) ? trim($this->input('email')) : $this->input('email'),
            'phone' => is_string($this->input('phone')) ? trim($this->input('phone')) : $this->input('phone'),
            'address' => is_string($this->input('address')) ? trim($this->input('address')) : $this->input('address'),
            'city' => is_string($this->input('city')) ? trim($this->input('city')) : $this->input('city'),
            'country' => is_string($this->input('country')) ? strtoupper(trim($this->input('country'))) : $this->input('country'),
            'bank_name' => is_string($this->input('bank_name')) ? trim($this->input('bank_name')) : $this->input('bank_name'),
            'iban' => $iban,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // No custom messages needed for now
        ];
    }
}