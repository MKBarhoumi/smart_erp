<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DocumentTypeCode;
use App\Enums\IdentifierType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
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
            // Document info
            'document_type_code' => ['required', Rule::enum(DocumentTypeCode::class)],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Sender (seller) - function code I-62
            'sender_identifier' => ['required', 'string', 'max:35'],
            'sender_type' => ['nullable', Rule::enum(IdentifierType::class)],
            'sender_name' => ['required', 'string', 'max:200'],
            'sender_address_description' => ['nullable', 'string', 'max:500'],
            'sender_street' => ['nullable', 'string', 'max:35'],
            'sender_city' => ['nullable', 'string', 'max:35'],
            'sender_postal_code' => ['nullable', 'string', 'max:17'],
            'sender_country' => ['nullable', 'string', 'max:6'],

            // Receiver (buyer) - function code I-64
            'receiver_identifier' => ['required', 'string', 'max:35'],
            'receiver_type' => ['nullable', Rule::enum(IdentifierType::class)],
            'receiver_name' => ['required', 'string', 'max:200'],
            'receiver_address_description' => ['nullable', 'string', 'max:500'],
            'receiver_street' => ['nullable', 'string', 'max:35'],
            'receiver_city' => ['nullable', 'string', 'max:35'],
            'receiver_postal_code' => ['nullable', 'string', 'max:17'],
            'receiver_country' => ['nullable', 'string', 'max:6'],

            // Lines
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'exists:products,id'],
            'lines.*.item_code' => ['required', 'string', 'max:35'],
            'lines.*.item_description' => ['required', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_of_measure' => ['nullable', 'string', 'max:8'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tva_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'At least one invoice line is required.',
            'lines.min' => 'At least one invoice line is required.',
            'lines.*.quantity.gt' => 'Quantity must be greater than zero.',
            'sender_identifier.required' => 'Sender identifier (matricule fiscal) is required.',
            'sender_name.required' => 'Sender name is required.',
            'receiver_identifier.required' => 'Receiver identifier (matricule fiscal) is required.',
            'receiver_name.required' => 'Receiver name is required.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default sender type if not provided
        if (!$this->sender_type) {
            $this->merge(['sender_type' => 'I-01']);
        }

        // Set default receiver type if not provided
        if (!$this->receiver_type) {
            $this->merge(['receiver_type' => 'I-01']);
        }
    }
}
