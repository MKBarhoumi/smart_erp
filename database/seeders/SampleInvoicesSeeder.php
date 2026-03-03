<?php

namespace Database\Seeders;

use App\Enums\DocumentTypeCode;
use App\Enums\IdentifierType;
use App\Enums\InvoiceStatus;
use App\Enums\OldInvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoicePartner;
use App\Models\InvoiceTax;
use App\Models\OldInvoice;
use App\Models\OldInvoiceLine;
use App\Models\OldInvoiceTaxLine;
use App\Models\Product;
use App\Models\User;
use App\Models\CompanySetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder to create sample invoices with various statuses to populate the dashboard.
 */
class SampleInvoicesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating sample invoices for dashboard data...');

        // First ensure we have at least one user and some customers
        $user = User::first() ?? User::factory()->create(['email' => 'demo@example.com', 'name' => 'Demo User']);
        $customers = Customer::take(5)->get();
        $products = Product::take(10)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Please run QuickRandomSeeder first to create customers and products.');
            return;
        }

        $company = CompanySetting::first();

        // 1. Create OldInvoices with current month dates (fixes Monthly Revenue)
        $this->createOldInvoices($user, $customers, $products);

        // 2. Create TEIF Invoices with various statuses
        $this->createTeifInvoices($user, $customers, $products, $company);

        $this->command->info('Sample invoices created successfully!');
    }

    private function createOldInvoices($user, $customers, $products): void
    {
        $this->command->info('Creating OldInvoice records for current month...');

        $now = now();
        $currentMonth = $now->format('Y-m');

        // Create 10 OldInvoices spread through the current month
        $statuses = [
            ['status' => OldInvoiceStatus::ACCEPTED, 'count' => 3],
            ['status' => OldInvoiceStatus::VALIDATED, 'count' => 2],
            ['status' => OldInvoiceStatus::SIGNED, 'count' => 2],
            ['status' => OldInvoiceStatus::SUBMITTED, 'count' => 2],
            ['status' => OldInvoiceStatus::DRAFT, 'count' => 1],
        ];

        $counter = OldInvoice::count() + 1;

        foreach ($statuses as $statusGroup) {
            for ($i = 0; $i < $statusGroup['count']; $i++) {
                $customer = $customers->random();
                $invoiceDate = $now->copy()->startOfMonth()->addDays(rand(0, min($now->day, 28)));

                // Create the invoice
                $invoice = OldInvoice::create([
                    'customer_id' => $customer->id,
                    'created_by' => $user->id,
                    'oldinvoice_number' => 'FA/' . date('Y') . '/' . str_pad((string) $counter++, 5, '0', STR_PAD_LEFT),
                    'document_identifier' => 'DOC/' . $currentMonth . '/' . Str::uuid()->toString(),
                    'document_type_code' => DocumentTypeCode::FACTURE,
                    'status' => $statusGroup['status'],
                    'oldinvoice_date' => $invoiceDate->format('Y-m-d'),
                    'due_date' => $invoiceDate->copy()->addDays(30)->format('Y-m-d'),
                    'total_gross' => '0.000',
                    'total_discount' => '0.000',
                    'total_net_before_disc' => '0.000',
                    'total_ht' => '0.000',
                    'total_tva' => '0.000',
                    'timbre_fiscal' => '0.000',
                    'total_ttc' => '0.000',
                ]);

                // Handle status-specific fields
                if ($statusGroup['status'] === OldInvoiceStatus::ACCEPTED) {
                    $invoice->ref_ttn_val = 'TTN-' . str_pad((string) rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
                    $invoice->accepted_at = now();
                    $invoice->save();
                }

                // Add lines
                $this->addOldInvoiceLines($invoice, $products);
            }
        }

        $this->command->info('Created ' . array_sum(array_column($statuses, 'count')) . ' OldInvoice records.');
    }

    private function addOldInvoiceLines(OldInvoice $invoice, $products): void
    {
        $lineCount = rand(2, 4);
        $totalGross = '0.000';
        $totalDiscount = '0.000';
        $totalHT = '0.000';
        $taxTotals = [];

        for ($l = 1; $l <= $lineCount; $l++) {
            $product = $products->random();
            $quantity = (float) rand(1, 10) + (rand(0, 999) / 1000);
            $unitPrice = (float) $product->unit_price;
            $discountRate = (float) [0, 5, 10][array_rand([0, 5, 10])];

            $lineGross = bcmul((string) $quantity, (string) $unitPrice, 3);
            $discountAmount = bcmul($lineGross, bcdiv((string) $discountRate, '100', 4), 3);
            $lineNet = bcsub($lineGross, $discountAmount, 3);
            $tvaRate = (float) $product->tva_rate;
            $tvaAmount = bcmul($lineNet, bcdiv((string) $tvaRate, '100', 4), 3);
            $lineTotal = bcadd($lineNet, $tvaAmount, 3);

            OldInvoiceLine::create([
                'oldinvoice_id' => $invoice->id,
                'product_id' => $product->id,
                'line_number' => $l,
                'item_code' => $product->code,
                'item_description' => $product->name,
                'item_lang' => 'fr',
                'quantity' => (string) $quantity,
                'unit_of_measure' => $product->unit_of_measure,
                'unit_price' => (string) $unitPrice,
                'discount_rate' => (string) $discountRate,
                'discount_amount' => $discountAmount,
                'line_net_amount' => $lineNet,
                'tva_rate' => (string) $tvaRate,
                'tva_amount' => $tvaAmount,
                'line_total' => $lineTotal,
            ]);

            $totalGross = bcadd($totalGross, $lineGross, 3);
            $totalDiscount = bcadd($totalDiscount, $discountAmount, 3);
            $totalHT = bcadd($totalHT, $lineNet, 3);

            $rateKey = number_format($tvaRate, 2);
            if (!isset($taxTotals[$rateKey])) {
                $taxTotals[$rateKey] = ['taxable' => '0.000', 'tax' => '0.000'];
            }
            $taxTotals[$rateKey]['taxable'] = bcadd($taxTotals[$rateKey]['taxable'], $lineNet, 3);
            $taxTotals[$rateKey]['tax'] = bcadd($taxTotals[$rateKey]['tax'], $tvaAmount, 3);
        }

        // Create tax lines
        $totalTVA = '0.000';
        foreach ($taxTotals as $rate => $amounts) {
            OldInvoiceTaxLine::create([
                'oldinvoice_id' => $invoice->id,
                'tax_type_code' => 'I-1601',
                'tax_type_name' => 'TVA',
                'tax_rate' => $rate,
                'taxable_amount' => $amounts['taxable'],
                'tax_amount' => $amounts['tax'],
            ]);
            $totalTVA = bcadd($totalTVA, $amounts['tax'], 3);
        }

        // Calculate timbre if needed
        $hasTimbre = $invoice->lines()
            ->whereHas('product', fn($q) => $q->where('is_subject_to_timbre', true))
            ->exists();
        $timbre = $hasTimbre ? '1.000' : '0.000';
        $totalTTC = bcadd(bcadd($totalHT, $totalTVA, 3), $timbre, 3);

        $invoice->update([
            'total_gross' => $totalGross,
            'total_discount' => $totalDiscount,
            'total_net_before_disc' => $totalGross,
            'total_ht' => $totalHT,
            'total_tva' => $totalTVA,
            'timbre_fiscal' => $timbre,
            'total_ttc' => $totalTTC,
        ]);
    }

    private function createTeifInvoices($user, $customers, $products, $company): void
    {
        $this->command->info('Creating TEIF Invoice records...');

        $now = now();
        $senderIdentifier = $company ? $company->matricule_fiscal : '1234567ABC';
        $senderName = $company ? $company->company_name : 'NovERP Company';

        // Create invoices with each status
        $statuses = [
            ['status' => InvoiceStatus::ACCEPTED, 'count' => 2],
            ['status' => InvoiceStatus::VALIDATED, 'count' => 2],
            ['status' => InvoiceStatus::SIGNED, 'count' => 2],
            ['status' => InvoiceStatus::SUBMITTED, 'count' => 2],
            ['status' => InvoiceStatus::DRAFT, 'count' => 1],
        ];

        $counter = Invoice::count() + 1;

        foreach ($statuses as $statusGroup) {
            for ($i = 0; $i < $statusGroup['count']; $i++) {
                $customer = $customers->random();
                $invoiceDate = $now->copy()->subDays(rand(0, 30))->format('Y-m-d');

                // Create the invoice with placeholder amounts (will be updated by addInvoiceLines)
                $invoice = Invoice::create([
                    'version' => '1.8.8',
                    'controlling_agency' => 'TN',
                    'sender_identifier' => $senderIdentifier,
                    'sender_type' => IdentifierType::MATRICULE_FISCAL->value,
                    'receiver_identifier' => $customer->identifier_value,
                    'receiver_type' => $customer->identifier_type->value ?? IdentifierType::MATRICULE_FISCAL->value,
                    'document_identifier' => 'TEIF/' . $now->format('Y') . '/' . str_pad((string) $counter++, 6, '0', STR_PAD_LEFT),
                    'document_type_code' => 'CO380',
                    'document_type_name' => 'Facture Standard',
                    'dates' => [
                        ['date_type_code' => 'I-124', 'date_value' => $invoiceDate],
                    ],
                    'invoice_amounts' => [
                        ['amount_type_code' => 'I-176', 'amount' => '0.000', 'currency' => 'TND'],
                        ['amount_type_code' => 'I-181', 'amount' => '0.000', 'currency' => 'TND'],
                        ['amount_type_code' => 'I-180', 'amount' => '0.000', 'currency' => 'TND'],
                    ],
                    'status' => $statusGroup['status']->value,
                    'created_by' => $user->id,
                ]);

                // Handle status-specific fields
                if ($statusGroup['status'] === InvoiceStatus::ACCEPTED) {
                    $invoice->ref_ttn_value = 'CEV-' . Str::uuid()->toString();
                    $invoice->accepted_at = now();
                    $invoice->save();
                } elseif ($statusGroup['status'] === InvoiceStatus::SUBMITTED) {
                    $invoice->submitted_at = now()->subHours(rand(1, 24));
                    $invoice->save();
                }

                // Add partners
                $this->addInvoicePartners($invoice, $customer, $senderName, $senderIdentifier, $company);

                // Add lines
                $this->addInvoiceLines($invoice, $products);

                // Add taxes
                $this->addInvoiceTaxes($invoice);
            }
        }

        $this->command->info('Created ' . array_sum(array_column($statuses, 'count')) . ' TEIF Invoice records.');
    }

    private function addInvoicePartners(Invoice $invoice, $customer, $senderName, $senderIdentifier, $company): void
    {
        // Sender (I-62)
        InvoicePartner::create([
            'invoice_id' => $invoice->id,
            'function_code' => 'I-62',
            'partner_identifier_type' => IdentifierType::MATRICULE_FISCAL->value,
            'partner_identifier' => $senderIdentifier,
            'partner_name' => $senderName,
            'street' => $company->street ?? 'Rue de l\'Industrie',
            'city' => $company->city ?? 'Tunis',
            'postal_code' => $company->postal_code ?? '1000',
            'country' => $company->country_code ?? 'TN',
        ]);

        // Receiver (I-64)
        InvoicePartner::create([
            'invoice_id' => $invoice->id,
            'function_code' => 'I-64',
            'partner_identifier_type' => $customer->identifier_type->value ?? IdentifierType::MATRICULE_FISCAL->value,
            'partner_identifier' => $customer->identifier_value,
            'partner_name' => $customer->name,
            'street' => $customer->street,
            'city' => $customer->city,
            'postal_code' => $customer->postal_code,
            'country' => $customer->country_code ?? 'TN',
        ]);
    }

    private function addInvoiceLines(Invoice $invoice, $products): void
    {
        $lineCount = rand(2, 4);
        $totalHT = '0.000';
        $totalTVA = '0.000';

        for ($l = 1; $l <= $lineCount; $l++) {
            $product = $products->random();
            $quantity = (float) rand(1, 10) + (rand(0, 999) / 1000);
            $unitPrice = (float) $product->unit_price;
            $lineNet = bcmul((string) $quantity, (string) $unitPrice, 3);
            $tvaRate = (float) $product->tva_rate;
            $tvaAmount = bcmul($lineNet, bcdiv((string) $tvaRate, '100', 4), 3);

            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'item_identifier' => (string) $l,
                'item_code' => $product->code,
                'item_code_type' => 'I-140',
                'item_description' => $product->name,
                'item_lang' => 'fr',
                'quantity' => (string) $quantity,
                'measurement_unit' => $product->unit_of_measure,
                'packaging_level' => 'I-158',
                'tax_type_code' => 'I-1601',
                'tax_rate' => (string) $tvaRate,
                'amounts' => [
                    ['amount_type_code' => 'I-183', 'amount' => number_format($unitPrice, 3, '.', '')],
                    ['amount_type_code' => 'I-171', 'amount' => $lineNet],
                ],
            ]);

            $totalHT = bcadd($totalHT, $lineNet, 3);
            $totalTVA = bcadd($totalTVA, $tvaAmount, 3);
        }

        $totalTTC = bcadd($totalHT, $totalTVA, 3);

        $invoice->update([
            'invoice_amounts' => [
                ['amount_type_code' => 'I-176', 'amount' => $totalHT],
                ['amount_type_code' => 'I-181', 'amount' => $totalTVA],
                ['amount_type_code' => 'I-180', 'amount' => $totalTTC],
            ],
        ]);
    }

    private function addInvoiceTaxes(Invoice $invoice): void
    {
        // Group lines by tax rate
        $taxGroups = [];
        foreach ($invoice->lines as $line) {
            $rate = (float) $line->tax_rate;
            $rateKey = number_format($rate, 2);

            $lineAmount = '0.000';
            if (is_array($line->amounts)) {
                foreach ($line->amounts as $amount) {
                    if (($amount['amount_type_code'] ?? '') === 'I-171') {
                        $lineAmount = $amount['amount'] ?? '0.000';
                    }
                }
            }

            if (!isset($taxGroups[$rateKey])) {
                $taxGroups[$rateKey] = '0.000';
            }
            $taxGroups[$rateKey] = bcadd($taxGroups[$rateKey], $lineAmount, 3);
        }

        foreach ($taxGroups as $rate => $taxableAmount) {
            $taxAmount = bcmul($taxableAmount, bcdiv($rate, '100', 4), 3);

            InvoiceTax::create([
                'invoice_id' => $invoice->id,
                'tax_type_code' => 'I-1601',
                'tax_type_name' => 'TVA',
                'tax_rate' => $rate,
                'amounts' => [
                    ['amount_type_code' => 'I-177', 'amount' => $taxableAmount],
                    ['amount_type_code' => 'I-178', 'amount' => $taxAmount],
                ],
            ]);
        }
    }
}
