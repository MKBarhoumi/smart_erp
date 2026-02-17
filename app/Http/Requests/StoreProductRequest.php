<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', 'unique:products,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'item_lang' => ['nullable', 'string', 'max:5'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:99999999999999999.999'],
            'unit_of_measure' => ['nullable', 'string', 'max:10'],
            'tva_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_subject_to_timbre' => ['boolean'],
            'track_inventory' => ['boolean'],
            'current_stock' => ['nullable', 'numeric', 'min:0'],
            'min_stock_alert' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
