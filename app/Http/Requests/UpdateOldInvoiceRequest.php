<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DocumentTypeCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOldInvoiceRequest extends FormRequest
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
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'document_type_code' => ['required', Rule::enum(DocumentTypeCode::class)],
            'oldinvoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:oldinvoice_date'],
            'billing_period_start' => ['nullable', 'date'],
            'billing_period_end' => ['nullable', 'date', 'after_or_equal:billing_period_start'],
            'parent_oldinvoice_id' => ['nullable', 'uuid', 'exists:oldinvoices,id'],
            'timbre_fiscal' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Lines
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'uuid'],
            'lines.*.product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'lines.*.item_code' => ['required', 'string', 'max:50'],
            'lines.*.item_description' => ['required', 'string', 'max:500'],
            'lines.*.item_lang' => ['nullable', 'string', 'max:5'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_of_measure' => ['nullable', 'string', 'max:10'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tva_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'At least one oldinvoice line is required.',
            'lines.min' => 'At least one oldinvoice line is required.',
            'lines.*.quantity.gt' => 'Quantity must be greater than zero.',
        ];
    }
}
