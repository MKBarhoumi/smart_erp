<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoicePartner;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoicePartnerFactory extends Factory
{
    protected $model = InvoicePartner::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'function_code' => $this->faker->randomElement(['I-61', 'I-62', 'I-64']),
            'partner_identifier' => $this->faker->numerify('0######') . $this->faker->lexify('?AM000'),
            'partner_identifier_type' => 'I-01',
            'partner_name' => $this->faker->company(),
            'partner_name_type' => 'Qualification',
            'address_description' => $this->faker->address(),
            'street' => $this->faker->streetName(),
            'city' => $this->faker->city(),
            'postal_code' => $this->faker->postcode(),
            'country' => 'TN',
            'country_code_list' => 'ISO_3166-1',
            'address_lang' => 'fr',
            'locations' => [],
            'references' => [
                [
                    'ref_id' => 'I-815',
                    'value' => $this->faker->numerify('B#########'),
                    'dates' => [],
                ],
            ],
            'contacts' => [
                [
                    'function_code' => 'I-94',
                    'contact_identifier' => $this->faker->lexify('???'),
                    'contact_name' => $this->faker->name(),
                    'com_means_type' => 'I-101',
                    'com_address' => $this->faker->phoneNumber(),
                ],
            ],
        ];
    }

    public function seller(): static
    {
        return $this->state(fn(array $attributes) => [
            'function_code' => 'I-61',
        ]);
    }

    public function buyer(): static
    {
        return $this->state(fn(array $attributes) => [
            'function_code' => 'I-62',
        ]);
    }
}
