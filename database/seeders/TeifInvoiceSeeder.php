<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoicePartner;
use App\Models\InvoiceTax;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TeifInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        
        // Ensure we have at least one user
        $user = User::first() ?? User::factory()->create();

        // Get existing data
        $customers = Customer::all();
        $products = Product::all();

        if ($customers->isEmpty()) {
            $this->command->error('No customers found. Please seed customers first.');
            return;
        }

        if ($products->isEmpty()) {
            $this->command->error('No products found. Please seed products first.');
            return;
        }

        $startDate = Carbon::create(2026, 1, 1);
        $endDate = Carbon::create(2026, 4, 23);

        $this->command->info('Cleaning up previous seeded invoices in range INV-2026-00001 to INV-2026-00060...');
        
        $existingInvoices = Invoice::where('document_identifier', 'like', 'INV-2026-%')
            ->whereBetween(DB::raw('CAST(SUBSTRING(document_identifier, 10) AS UNSIGNED)'), [1, 60])
            ->get();
            
        foreach ($existingInvoices as $inv) {
            $inv->forceDelete(); // Using forceDelete to truly clean up
        }

        $this->command->info('Seeding 60 TEIF invoices with real customer and product data...');

        for ($i = 1; $i <= 60; $i++) {
            $docId = 'INV-2026-' . str_pad($i, 5, '0', STR_PAD_LEFT);
            $randomDate = Carbon::createFromTimestamp(rand($startDate->timestamp, $endDate->timestamp));
            
            $senderId = '1234567A';
            $customer = $customers->random();
            $receiverId = $customer->identifier_value;

            // Create Invoice
            $invoice = Invoice::create([
                'created_by' => $user->id,
                'version' => '1.8.8',
                'controlling_agency' => 'TTN',
                'sender_identifier' => $senderId,
                'sender_type' => 'I-01',
                'receiver_identifier' => $receiverId,
                'receiver_type' => 'I-01',
                'document_identifier' => $docId,
                'document_type_code' => 'I-11',
                'document_type_name' => 'Facture',
                'dates' => [
                    [
                        'function_code' => 'I-31',
                        'format' => 'ddMMyy',
                        'value' => $randomDate->format('dmy'),
                    ],
                ],
                'status' => $faker->randomElement(['draft', 'validated', 'signed', 'submitted', 'accepted']),
                'invoice_amounts' => [], 
                'payment_section' => [
                    [
                        'payment_terms_type_code' => 'I-114',
                        'payment_terms_description' => 'Paiement à 30 jours',
                        'fii' => [
                            'function_code' => 'I-141',
                            'institution_name' => $faker->company() . ' Bank',
                            'account_number' => $faker->iban('TN'),
                        ],
                    ],
                ],
            ]);

            // Create Partners
            InvoicePartner::create([
                'invoice_id' => $invoice->id,
                'function_code' => 'I-62', // Sender
                'partner_identifier' => $senderId,
                'partner_identifier_type' => 'I-01',
                'partner_name' => 'NovERP Solutions SARL',
                'partner_name_type' => 'Qualification',
                'street' => 'Rue de l\'Industrie, N°45',
                'city' => 'Tunis',
                'postal_code' => '1002',
                'country' => 'TN',
            ]);

            InvoicePartner::create([
                'invoice_id' => $invoice->id,
                'function_code' => 'I-64', // Receiver
                'partner_identifier' => $receiverId,
                'partner_identifier_type' => 'I-01',
                'partner_name' => $customer->name,
                'partner_name_type' => 'Qualification',
                'street' => $customer->street,
                'city' => $customer->city,
                'postal_code' => $customer->postal_code,
                'country' => $customer->country_code ?? 'TN',
            ]);

            // Create 2-5 lines
            $totalHt = 0;
            $taxGroups = [];
            $lineCount = rand(2, 5);

            for ($j = 1; $j <= $lineCount; $j++) {
                $product = $products->random();
                $qty = rand(1, 10);
                $unitPrice = (float) $product->unit_price;
                $lineHt = $qty * $unitPrice;
                $taxRate = (float) $product->tva_rate;
                $lineTva = $lineHt * ($taxRate / 100);

                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'item_identifier' => (string)$j,
                    'item_code' => $product->code,
                    'item_description' => $product->name,
                    'quantity' => (string)$qty,
                    'measurement_unit' => $product->unit_of_measure ?? 'UNIT',
                    'tax_type_code' => 'I-1602',
                    'tax_type_name' => 'TVA',
                    'tax_rate' => number_format($taxRate, 3, '.', ''),
                    'amounts' => [
                        [
                            'amount_type_code' => 'I-183', // Unit Price
                            'currency_identifier' => 'TND',
                            'amount' => number_format($unitPrice, 3, '.', ''),
                        ],
                        [
                            'amount_type_code' => 'I-171', // Line Total
                            'currency_identifier' => 'TND',
                            'amount' => number_format($lineHt, 3, '.', ''),
                        ],
                    ],
                ]);

                $totalHt += $lineHt;
                
                $rateKey = number_format($taxRate, 3, '.', '');
                if (!isset($taxGroups[$rateKey])) {
                    $taxGroups[$rateKey] = [
                        'taxable' => 0,
                        'tax' => 0
                    ];
                }
                $taxGroups[$rateKey]['taxable'] += $lineHt;
                $taxGroups[$rateKey]['tax'] += $lineTva;
            }

            $totalTva = 0;
            foreach ($taxGroups as $rate => $values) {
                InvoiceTax::create([
                    'invoice_id' => $invoice->id,
                    'tax_type_code' => 'I-1602',
                    'tax_type_name' => 'TVA',
                    'tax_rate' => $rate,
                    'amounts' => [
                        [
                            'amount_type_code' => 'I-177', // Taxable Amount
                            'currency_identifier' => 'TND',
                            'amount' => number_format($values['taxable'], 3, '.', ''),
                        ],
                        [
                            'amount_type_code' => 'I-178', // Tax Amount
                            'currency_identifier' => 'TND',
                            'amount' => number_format($values['tax'], 3, '.', ''),
                        ],
                    ],
                ]);
                $totalTva += $values['tax'];
            }

            $totalTtc = $totalHt + $totalTva;

            // Update Invoice amounts
            $invoice->update([
                'invoice_amounts' => [
                    [
                        'amount_type_code' => 'I-176', // Total HT
                        'currency_identifier' => 'TND',
                        'amount' => number_format($totalHt, 3, '.', ''),
                    ],
                    [
                        'amount_type_code' => 'I-181', // Total TVA
                        'currency_identifier' => 'TND',
                        'amount' => number_format($totalTva, 3, '.', ''),
                    ],
                    [
                        'amount_type_code' => 'I-180', // Total TTC
                        'currency_identifier' => 'TND',
                        'amount' => number_format($totalTtc, 3, '.', ''),
                        'description' => 'Total en dinars',
                        'description_lang' => 'fr',
                    ],
                ],
            ]);
        }

        $this->command->info('Successfully seeded 60 invoices with realistic data.');
    }
}
