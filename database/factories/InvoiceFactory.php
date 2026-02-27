<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'version' => '1.8.8',
            'controlling_agency' => 'TTN',
            'sender_identifier' => '0736202XAM000',
            'sender_type' => 'I-01',
            'receiver_identifier' => '0914089JAM000',
            'receiver_type' => 'I-01',
            'document_identifier' => 'FA-' . date('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'document_type_code' => 'I-11',
            'document_type_name' => 'Facture',
            'dates' => [
                ['function_code' => 'I-31', 'format' => 'ddMMyy', 'value' => date('dmy')],
            ],
            'payment_section' => [
                [
                    'payment_terms_type_code' => 'I-114',
                    'payment_terms_description' => 'Payment due within 30 days',
                    'dtm' => [],
                    'moa' => null,
                    'pai' => null,
                    'fii' => [
                        'function_code' => 'I-141',
                        'account_number' => '07078010011234567890123',
                        'owner_identifier' => '1B',
                        'name_code' => '0707',
                        'branch_identifier' => '010',
                        'institution_name' => 'Banque Nationale',
                    ],
                ],
            ],
            'free_texts' => [],
            'special_conditions' => [],
            'loc_section' => [],
            'invoice_amounts' => [
                [
                    'amount_type_code' => 'I-176',
                    'currency_code_list' => 'ISO_4217',
                    'currency_identifier' => 'TND',
                    'amount' => '100.000',
                    'description' => null,
                    'description_lang' => null,
                ],
                [
                    'amount_type_code' => 'I-180',
                    'currency_code_list' => 'ISO_4217',
                    'currency_identifier' => 'TND',
                    'amount' => '119.000',
                    'description' => 'CENT DIX NEUF DINARS',
                    'description_lang' => 'fr',
                ],
                [
                    'amount_type_code' => 'I-181',
                    'currency_code_list' => 'ISO_4217',
                    'currency_identifier' => 'TND',
                    'amount' => '19.000',
                    'description' => null,
                    'description_lang' => null,
                ],
            ],
            'invoice_allowances' => [],
            'ref_ttn_id' => null,
            'ref_ttn_value' => null,
            'ref_cev' => null,
            'ref_ttn_dates' => null,
            'signatures' => [],
            'status' => 'draft',
        ];
    }

    public function signed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'signed',
            'signatures' => [
                [
                    'id' => 'SigFrs',
                    'value' => base64_encode('test-signature'),
                    'signing_time' => now()->toIso8601String(),
                    'role' => 'Fournisseur',
                    'certificate' => base64_encode('test-certificate'),
                ],
            ],
        ]);
    }

    public function validated(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'validated',
            'ref_ttn_id' => 'I-88',
            'ref_ttn_value' => 'TTN-' . $this->faker->uuid(),
            'ref_cev' => base64_encode('test-cev-qr'),
            'ref_ttn_dates' => [
                ['function_code' => 'I-33', 'format' => 'ddMMyy', 'value' => date('dmy')],
            ],
        ]);
    }
}
