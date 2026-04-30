<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', 'unique:services,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:100', 'in:IT,Consulting,Maintenance,Training,Other'],
            'unit' => ['required', 'string', 'max:50', 'in:Hour,Day,Unit,Forfait'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:99999999999999999.999'],
            'tax_rate' => ['required', 'numeric', 'in:0,7,13,19'],
            'discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
