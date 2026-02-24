<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceLineFactory extends Factory
{
    protected $model = InvoiceLine::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'parent_line_id' => null,
            'item_identifier' => (string) $this->faker->numberBetween(1, 100),
            'item_code' => strtoupper($this->faker->lexify('???')),
            'item_description' => $this->faker->sentence(3),
            'item_lang' => 'fr',
            'api_details' => [],
            'quantity' => (string) $this->faker->randomFloat(3, 1, 100),
            'measurement_unit' => $this->faker->randomElement(['UNIT', 'KG', 'M', 'L']),
            'dates' => [],
            'tax_type_code' => 'I-1602',
            'tax_type_name' => 'TVA',
            'tax_category' => 'S',
            'tax_rate' => '19',
            'tax_rate_basis' => null,
            'allowances' => [],
            'amounts' => [
                [
                    'amount_type_code' => 'I-183',
                    'currency_code_list' => 'ISO_4217',
                    'currency_identifier' => 'TND',
                    'amount' => (string) $this->faker->randomFloat(3, 10, 1000),
                    'description' => null,
                    'description_lang' => null,
                ],
                [
                    'amount_type_code' => 'I-171',
                    'currency_code_list' => 'ISO_4217',
                    'currency_identifier' => 'TND',
                    'amount' => (string) $this->faker->randomFloat(3, 10, 1000),
                    'description' => null,
                    'description_lang' => null,
                ],
            ],
            'free_texts' => [],
            'sort_order' => 0,
        ];
    }

    public function withSubLines(int $count = 2): static
    {
        return $this->afterCreating(function (InvoiceLine $line) use ($count) {
            InvoiceLine::factory()->count($count)->create([
                'invoice_id' => $line->invoice_id,
                'parent_line_id' => $line->id,
            ]);
        });
    }
}
