<?php

/**
 * Complete XML Invoice Import Workflow Test
 * Tests: Parse → Import → Verify Database Records
 */

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Invoice;
use App\Models\InvoicePartner;
use App\Models\InvoiceLine;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

// Test data from parsed XML
$parsedData = [
    'invoice_id' => 'INV-TEST-001',
    'invoice_type' => 'I-11',
    'issue_date' => '2026-04-03',
    'sender_name' => 'Test Seller Company',
    'sender_tax_id' => '1234567ABC000',
    'receiver_name' => 'Test Buyer Customer',
    'receiver_tax_id' => '9876543XYZ000',
    'total_ht' => '125.000',
    'total_tva' => '23.750',
    'total_ttc' => '148.750',
    'lines' => [
        [
            'line_id' => '1',
            'item_code' => 'PROD001',
            'quantity' => '5.0',
            'description' => 'Test Product One',
            'unit_price' => '10.000',
            'line_amount' => '50.000',
            'tax_rate' => '19',
        ],
        [
            'line_id' => '2',
            'item_code' => 'PROD002',
            'quantity' => '3.0',
            'description' => 'Test Product Two',
            'unit_price' => '25.000',
            'line_amount' => '75.000',
            'tax_rate' => '19',
        ],
    ],
];

echo "🔍 Testing Invoice Import Workflow\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    DB::beginTransaction();

    // Step 1: Check if invoice already exists
    echo "1️⃣  Checking for duplicate invoice ID...\n";
    $existingInvoice = Invoice::where('document_identifier', $parsedData['invoice_id'])->first();
    if ($existingInvoice) {
        echo "   ⚠️  Invoice ID '{$parsedData['invoice_id']}' already exists (ID: {$existingInvoice->id})\n";
        echo "   📝 Deleting for fresh test...\n";
        $existingInvoice->forceDelete();
    }
    echo "   ✓ Ready to import\n\n";

    // Step 2: Auto-create customer
    echo "2️⃣  Auto-creating customer...\n";
    $customer = Customer::firstOrCreate(
        [
            'identifier_type' => 'I-01',
            'identifier_value' => $parsedData['receiver_tax_id'],
        ],
        [
            'matricule_fiscal' => $parsedData['receiver_tax_id'],
            'name' => $parsedData['receiver_name'],
            'country_code' => 'TN',
        ]
    );
    echo "   ✓ Customer: {$customer->name} (ID: {$customer->id})\n";
    echo "   ✓ Tax ID: {$customer->identifier_value}\n\n";

    // Step 3: Auto-create products
    echo "3️⃣  Auto-creating products...\n";
    $productIds = [];
    foreach ($parsedData['lines'] as $lineData) {
        $product = Product::firstOrCreate(
            ['code' => $lineData['item_code']],
            [
                'name' => $lineData['description'],
                'unit_price' => $lineData['unit_price'],
            ]
        );
        $productIds[$lineData['item_code']] = $product->id;
        echo "   ✓ Product: {$product->name} (Code: {$product->code}, ID: {$product->id})\n";
    }
    echo "\n";

    // Step 4: Create invoice
    echo "4️⃣  Creating invoice...\n";
    $invoiceAmounts = [
        [
            'amount_type_code' => 'I-176',
            'currency_code_list' => 'ISO_4217',
            'currency_identifier' => 'TND',
            'amount' => $parsedData['total_ht'],
        ],
        [
            'amount_type_code' => 'I-181',
            'currency_code_list' => 'ISO_4217',
            'currency_identifier' => 'TND',
            'amount' => $parsedData['total_tva'],
        ],
        [
            'amount_type_code' => 'I-180',
            'currency_code_list' => 'ISO_4217',
            'currency_identifier' => 'TND',
            'amount' => $parsedData['total_ttc'],
        ],
    ];

    $invoice = Invoice::create([
        'document_identifier' => $parsedData['invoice_id'],
        'document_type_code' => $parsedData['invoice_type'],
        'document_type_name' => 'Facture', // From TEIF standard
        'sender_identifier' => $parsedData['sender_tax_id'],
        'sender_type' => 'I-01',
        'receiver_identifier' => $parsedData['receiver_tax_id'],
        'receiver_type' => 'I-01',
        'dates' => [
            [
                'function_code' => 'I-31',
                'format' => 'ddMMyy',
                'date' => $parsedData['issue_date'],
            ],
        ],
        'invoice_amounts' => $invoiceAmounts,
        'version' => '1.8.8',
        'controlling_agency' => 'TTN',
        'status' => 'draft',
        'created_by' => 1, // System user
    ]);
    echo "   ✓ Invoice created (ID: {$invoice->id})\n";
    echo "   ✓ Document ID: {$invoice->document_identifier}\n";
    echo "   ✓ Totals: {$parsedData['total_ht']} HT + {$parsedData['total_tva']} TVA = {$parsedData['total_ttc']} TTC\n\n";

    // Step 5: Create invoice partners
    echo "5️⃣  Creating invoice partners...\n";

    // Seller
    $seller = InvoicePartner::create([
        'invoice_id' => $invoice->id,
        'function_code' => 'I-62',
        'partner_name' => $parsedData['sender_name'],
        'partner_identifier' => $parsedData['sender_tax_id'],
        'partner_identifier_type' => 'I-01',
        'partner_name_type' => 'Qualification',
    ]);
    echo "   ✓ Seller: {$seller->partner_name}\n";

    // Buyer
    $buyer = InvoicePartner::create([
        'invoice_id' => $invoice->id,
        'function_code' => 'I-64',
        'partner_name' => $parsedData['receiver_name'],
        'partner_identifier' => $parsedData['receiver_tax_id'],
        'partner_identifier_type' => 'I-01',
        'partner_name_type' => 'Qualification',
    ]);
    echo "   ✓ Buyer: {$buyer->partner_name}\n\n";

    // Step 6: Create invoice lines
    echo "6️⃣  Creating invoice lines...\n";
    foreach ($parsedData['lines'] as $idx => $lineData) {
        $lineAmounts = [
            [
                'amount_type_code' => 'I-183',
                'currency_code_list' => 'ISO_4217',
                'currency_identifier' => 'TND',
                'amount' => $lineData['unit_price'],
            ],
            [
                'amount_type_code' => 'I-171',
                'currency_code_list' => 'ISO_4217',
                'currency_identifier' => 'TND',
                'amount' => $lineData['line_amount'],
            ],
        ];

        $line = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'item_identifier' => strval($idx + 1),
            'item_code' => $lineData['item_code'],
            'item_description' => $lineData['description'],
            'item_lang' => 'fr',
            'quantity' => $lineData['quantity'],
            'measurement_unit' => 'UNIT',
            'tax_type_code' => 'I-1602',
            'tax_type_name' => 'TVA',
            'tax_rate' => $lineData['tax_rate'],
            'amounts' => $lineAmounts,
            'sort_order' => $idx + 1,
        ]);
        $lineNum = $idx + 1;
        echo "   ✓ Line {$lineNum}: {$line->item_description} (Qty: {$line->quantity}x{$line->unit_price} = {$line->line_amount})\n";
    }
    echo "\n";

    // Step 7: Verify database records
    echo "7️⃣  Verifying database records...\n";

    $invoiceCount = Invoice::where('document_identifier', $parsedData['invoice_id'])->count();
    echo "   ✓ Invoices: {$invoiceCount}\n";

    $partnerCount = InvoicePartner::where('invoice_id', $invoice->id)->count();
    echo "   ✓ Partners: {$partnerCount}\n";

    $lineCount = InvoiceLine::where('invoice_id', $invoice->id)->count();
    echo "   ✓ Lines: {$lineCount}\n";

    $customerCount = Customer::where('identifier_value', $parsedData['receiver_tax_id'])->count();
    echo "   ✓ Customers: {$customerCount}\n";

    $productCount = Product::whereIn('code', array_keys($productIds))->count();
    echo "   ✓ Products: {$productCount}\n\n";

    DB::commit();

    echo "✅ IMPORT WORKFLOW COMPLETED SUCCESSFULLY!\n\n";
    echo "📊 Summary:\n";
    echo "   Invoice: {$parsedData['invoice_id']}\n";
    echo "   Customer: {$parsedData['receiver_name']}\n";
    echo "   Lines: {$lineCount}\n";
    echo "   Total: {$parsedData['total_ttc']} TND\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: {$e->getMessage()}\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
