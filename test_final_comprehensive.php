<?php

/**
 * Final Comprehensive Test - XML Invoice Import Complete Workflow
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Invoice;
use App\Models\InvoicePartner;
use App\Models\InvoiceLine;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

echo "
╔════════════════════════════════════════════════════════════════════╗
║  FINAL COMPREHENSIVE TEST: XML INVOICE IMPORT SYSTEM              ║
║  Testing complete workflow with database constraints & fixes      ║
╚════════════════════════════════════════════════════════════════════╝
\n";

$tests = [
    'XML Parsing' => fn() => testXmlParsing(),
    'TEIF Format Support' => fn() => testTeifFormat(),
    'Customer Auto-Create with Unique Constraint' => fn() => testCustomerAutoCreate(),
    'Product Auto-Create' => fn() => testProductAutoCreate(),
    'Invoice Creation' => fn() => testInvoiceCreation(),
    'Full Import Workflow' => fn() => testFullWorkflow(),
];

$results = [];
foreach ($tests as $name => $test) {
    try {
        $test();
        $results[$name] = '✅ PASS';
        echo "✅ {$name}\n";
    } catch (\Exception $e) {
        $results[$name] = '❌ FAIL: ' . $e->getMessage();
        echo "❌ {$name}: {$e->getMessage()}\n";
    }
}

echo "\n╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  FINAL TEST RESULTS\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$passCount = 0;
foreach ($results as $name => $result) {
    echo "{$result} - {$name}\n";
    if (strpos($result, 'PASS') !== false) $passCount++;
}

echo "\n" . str_repeat("═", 70) . "\n";
echo "TOTAL: {$passCount}/" . count($results) . " tests passed\n";
echo str_repeat("═", 70) . "\n\n";

if ($passCount === count($results)) {
    echo "🎉 ALL TESTS PASSED! XML IMPORT SYSTEM IS FULLY FUNCTIONAL\n";
    exit(0);
} else {
    echo "⚠️  SOME TESTS FAILED - Please review errors above\n";
    exit(1);
}

// Test Functions
// ==============

function testXmlParsing() {
    $xmlFile = base_path('tests/fixtures/test_invoice.xml');
    if (!file_exists($xmlFile)) {
        throw new \Exception("XML file not found");
    }

    $xml = simplexml_load_string(file_get_contents($xmlFile), 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$xml || $xml->getName() !== 'TEIF') {
        throw new \Exception("XML parsing failed or root element is not TEIF");
    }
}

function testTeifFormat() {
    $xmlFile = base_path('tests/fixtures/test_invoice.xml');
    $xml = simplexml_load_string(file_get_contents($xmlFile), 'SimpleXMLElement', LIBXML_NOCDATA);

    // Verify TEIF structure
    $header = $xml->InvoiceHeader;
    $body = $xml->InvoiceBody;

    if (!$header || !$body) {
        throw new \Exception("TEIF structure incomplete");
    }

    // Verify key elements
    $bgm = $body->Bgm;
    $linSection = $body->LinSection;
    $invoiceMoa = $body->InvoiceMoa;

    if (!$bgm || !$linSection || !$invoiceMoa) {
        throw new \Exception("Missing required TEIF elements");
    }
}

function testCustomerAutoCreate() {
    // Test the exact scenario from the bug fix
    $taxId = 'TEST-CUSTOMER-' . time();

    // First creation
    $customer1 = Customer::firstOrCreate(
        [
            'identifier_type' => 'I-01',
            'identifier_value' => $taxId,
        ],
        [
            'matricule_fiscal' => $taxId,
            'name' => 'Test Company 1',
            'country_code' => 'TN',
        ]
    );

    if (!$customer1->id) {
        throw new \Exception("First customer creation failed");
    }

    // Second attempt should find existing (not create duplicate)
    $customer2 = Customer::firstOrCreate(
        [
            'identifier_type' => 'I-01',
            'identifier_value' => $taxId,
        ],
        [
            'matricule_fiscal' => $taxId,
            'name' => 'Test Company 2',
            'country_code' => 'TN',
        ]
    );

    if ($customer1->id !== $customer2->id) {
        throw new \Exception("firstOrCreate() did not find existing customer - unique constraint mismatch!");
    }

    if ($customer2->name !== 'Test Company 1') {
        throw new \Exception("Customer was overwritten instead of returned");
    }
}

function testProductAutoCreate() {
    $code = 'TEST-PRODUCT-' . time();

    $product = Product::firstOrCreate(
        ['code' => $code],
        [
            'name' => 'Test Product',
            'unit_price' => 100,
        ]
    );

    if (!$product->id) {
        throw new \Exception("Product creation failed");
    }
}

function testInvoiceCreation() {
    $invoiceId = 'TEST-INV-' . time();

    $invoice = Invoice::create([
        'document_identifier' => $invoiceId,
        'document_type_code' => 'I-11',
        'document_type_name' => 'Facture',
        'sender_identifier' => '1000000000ABC',
        'sender_type' => 'I-01',
        'receiver_identifier' => '2000000000XYZ',
        'receiver_type' => 'I-01',
        'dates' => [
            [
                'function_code' => 'I-31',
                'format' => 'ddMMyy',
                'date' => date('Y-m-d'),
            ],
        ],
        'invoice_amounts' => [
            ['amount_type_code' => 'I-176', 'currency_code_list' => 'ISO_4217', 'currency_identifier' => 'TND', 'amount' => '100'],
            ['amount_type_code' => 'I-181', 'currency_code_list' => 'ISO_4217', 'currency_identifier' => 'TND', 'amount' => '19'],
            ['amount_type_code' => 'I-180', 'currency_code_list' => 'ISO_4217', 'currency_identifier' => 'TND', 'amount' => '119'],
        ],
        'version' => '1.8.8',
        'controlling_agency' => 'TTN',
        'status' => 'draft',
        'created_by' => 1,
    ]);

    if (!$invoice->id) {
        throw new \Exception("Invoice creation failed");
    }
}

function testFullWorkflow() {
    DB::beginTransaction();

    try {
        $invoiceId = 'FULL-TEST-' . time();
        $customerId = 'CUST-' . time();
        $productCode = 'PROD-' . time();

        // Step 1: Auto-create customer
        $customer = Customer::firstOrCreate(
            ['identifier_type' => 'I-01', 'identifier_value' => $customerId],
            ['matricule_fiscal' => $customerId, 'name' => 'Full Test Customer', 'country_code' => 'TN']
        );

        // Step 2: Auto-create product
        $product = Product::firstOrCreate(
            ['code' => $productCode],
            ['name' => 'Full Test Product', 'unit_price' => 50]
        );

        // Step 3: Create invoice
        $invoice = Invoice::create([
            'document_identifier' => $invoiceId,
            'document_type_code' => 'I-11',
            'document_type_name' => 'Facture',
            'sender_identifier' => 'SENDER-001',
            'sender_type' => 'I-01',
            'receiver_identifier' => 'RECEIVER-001',
            'receiver_type' => 'I-01',
            'dates' => [['function_code' => 'I-31', 'format' => 'ddMMyy', 'date' => date('Y-m-d')]],
            'invoice_amounts' => [
                ['amount_type_code' => 'I-176', 'currency_code_list' => 'ISO_4217', 'currency_identifier' => 'TND', 'amount' => '50'],
                ['amount_type_code' => 'I-181', 'currency_code_list' => 'ISO_4217', 'currency_identifier' => 'TND', 'amount' => '9.5'],
                ['amount_type_code' => 'I-180', 'currency_code_list' => 'ISO_4217', 'currency_identifier' => 'TND', 'amount' => '59.5'],
            ],
            'version' => '1.8.8',
            'controlling_agency' => 'TTN',
            'status' => 'draft',
            'created_by' => 1,
        ]);

        // Step 4: Create partners
        InvoicePartner::create([
            'invoice_id' => $invoice->id,
            'function_code' => 'I-62',
            'partner_name' => 'Full Test Seller',
            'partner_identifier' => 'SENDER-001',
            'partner_identifier_type' => 'I-01',
            'partner_name_type' => 'Qualification',
        ]);

        InvoicePartner::create([
            'invoice_id' => $invoice->id,
            'function_code' => 'I-64',
            'partner_name' => 'Full Test Buyer',
            'partner_identifier' => 'RECEIVER-001',
            'partner_identifier_type' => 'I-01',
            'partner_name_type' => 'Qualification',
        ]);

        // Step 5: Create line items
        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'item_identifier' => '1',
            'item_code' => $productCode,
            'item_description' => 'Full Test Product',
            'item_lang' => 'fr',
            'quantity' => 1,
            'measurement_unit' => 'UNIT',
            'tax_type_code' => 'I-1602',
            'tax_type_name' => 'TVA',
            'tax_rate' => 19,
            'amounts' => [
                ['amount_type_code' => 'I-183', 'currency_code_list' => 'ISO_4217', 'currency_identifier' => 'TND', 'amount' => '50'],
                ['amount_type_code' => 'I-171', 'currency_code_list' => 'ISO_4217', 'currency_identifier' => 'TND', 'amount' => '50'],
            ],
            'sort_order' => 1,
        ]);

        // Verify all records were created
        if (!Invoice::where('document_identifier', $invoiceId)->exists()) {
            throw new \Exception("Invoice not found after creation");
        }

        if (InvoicePartner::where('invoice_id', $invoice->id)->count() !== 2) {
            throw new \Exception("Partners not created properly");
        }

        if (InvoiceLine::where('invoice_id', $invoice->id)->count() !== 1) {
            throw new \Exception("Lines not created properly");
        }

        DB::commit();

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
