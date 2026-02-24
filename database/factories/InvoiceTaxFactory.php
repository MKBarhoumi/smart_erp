<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceTax;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceTaxFactory extends Factory
{
    protected $model = InvoiceTax::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'tax_type_code' => 'I-1602',
            'tax_type_name' => 'TVA',
            'tax_category' => 'S',
            'tax_rate' => '19',
            'tax_rate_basis' => null,
            'amounts' => [
                [
                    'amount_type_code' => 'I-177',
                    'currency_code_list' => 'ISO_4217',
                    'currency_identifier' => 'TND',
                    'amount' => '100.000',
                    'description' => null,
                    'description_lang' => null,
                ],
                [
                    'amount_type_code' => 'I-178',
                    'currency_code_list' => 'ISO_4217',
                    'currency_identifier' => 'TND',
                    'amount' => '19.000',
                    'description' => null,
                    'description_lang' => null,
                ],
            ],
        ];
    }

    public function timbre(): static
    {
        return $this->state(fn(array $attributes) => [
            'tax_type_code' => 'I-1601',
            'tax_type_name' => 'droit de timbre',
            'tax_category' => null,
            'tax_rate' => '0',
            'amounts' => [
                [
                    'amount_type_code' => 'I-178',
                    'currency_code_list' => 'ISO_4217',
                    'currency_identifier' => 'TND',
                    'amount' => '1.000',
                    'description' => null,
                    'description_lang' => null,
                ],
            ],
        ]);
    }
}
