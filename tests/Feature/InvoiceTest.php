<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoicePartner;
use App\Models\InvoiceTax;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_is_created_with_relationships(): void
    {
        // Create complete invoice with relationships
        $invoice = Invoice::factory()->create();
        
        $partners = InvoicePartner::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);
        
        $lines = InvoiceLine::factory()->count(3)->create([
            'invoice_id' => $invoice->id,
        ]);
        
        $taxes = InvoiceTax::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);

        $invoice->refresh();
        $invoice->load(['partners', 'lines', 'taxes']);

        $this->assertCount(2, $invoice->partners);
        $this->assertCount(3, $invoice->lines);
        $this->assertCount(2, $invoice->taxes);
    }

    public function test_invoice_cascade_deletes_related_models(): void
    {
        $invoice = Invoice::factory()->create();
        
        InvoicePartner::factory()->create(['invoice_id' => $invoice->id]);
        InvoiceLine::factory()->create(['invoice_id' => $invoice->id]);
        InvoiceTax::factory()->create(['invoice_id' => $invoice->id]);

        $invoiceId = $invoice->id;

        // Force delete to test cascade
        $invoice->forceDelete();

        $this->assertDatabaseMissing('invoice_partners', ['invoice_id' => $invoiceId]);
        $this->assertDatabaseMissing('invoice_lines', ['invoice_id' => $invoiceId]);
        $this->assertDatabaseMissing('invoice_taxes', ['invoice_id' => $invoiceId]);
    }

    public function test_invoice_status_transitions(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft']);
        $this->assertEquals('draft', $invoice->status);

        $invoice->update(['status' => 'signed']);
        $this->assertEquals('signed', $invoice->status);

        $invoice->update(['status' => 'validated']);
        $this->assertEquals('validated', $invoice->status);
    }

    public function test_invoice_stores_signatures_as_json(): void
    {
        $signatures = [
            [
                'id' => 'SigFrs',
                'value' => base64_encode('test-signature'),
                'signing_time' => '2026-02-15T10:00:00Z',
                'role' => 'Fournisseur',
                'certificate' => base64_encode('test-certificate'),
            ],
        ];

        $invoice = Invoice::factory()->create(['signatures' => $signatures]);
        $invoice->refresh();

        $this->assertIsArray($invoice->signatures);
        $this->assertEquals('SigFrs', $invoice->signatures[0]['id']);
    }

    public function test_invoice_ref_ttn_val_fields(): void
    {
        $invoice = Invoice::factory()->validated()->create();

        $this->assertNotNull($invoice->ref_ttn_id);
        $this->assertNotNull($invoice->ref_ttn_value);
        $this->assertNotNull($invoice->ref_cev);
        $this->assertIsArray($invoice->ref_ttn_dates);
    }

    public function test_invoice_line_with_sub_lines(): void
    {
        $invoice = Invoice::factory()->create();
        
        $parentLine = InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'parent_line_id' => null,
        ]);

        $subLines = InvoiceLine::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
            'parent_line_id' => $parentLine->id,
        ]);

        $parentLine->refresh();
        $parentLine->load('subLines');

        $this->assertCount(2, $parentLine->subLines);
        
        foreach ($subLines as $subLine) {
            $this->assertEquals($parentLine->id, $subLine->parent_line_id);
        }
    }

    public function test_invoice_partner_function_codes(): void
    {
        $invoice = Invoice::factory()->create();

        $seller = InvoicePartner::factory()->seller()->create(['invoice_id' => $invoice->id]);
        $buyer = InvoicePartner::factory()->buyer()->create(['invoice_id' => $invoice->id]);

        $this->assertEquals('I-61', $seller->function_code);
        $this->assertEquals('I-62', $buyer->function_code);
    }

    public function test_invoice_tax_types(): void
    {
        $invoice = Invoice::factory()->create();

        $tva = InvoiceTax::factory()->create([
            'invoice_id' => $invoice->id,
            'tax_type_code' => 'I-1602',
            'tax_type_name' => 'TVA',
            'tax_rate' => '19',
        ]);

        $timbre = InvoiceTax::factory()->timbre()->create([
            'invoice_id' => $invoice->id,
        ]);

        $this->assertEquals('I-1602', $tva->tax_type_code);
        $this->assertEquals('I-1601', $timbre->tax_type_code);
    }

    public function test_invoice_amounts_structure(): void
    {
        $invoice = Invoice::factory()->create([
            'invoice_amounts' => [
                [
                    'amount_type_code' => 'I-176',
                    'currency_code_list' => 'ISO_4217',
                    'currency_identifier' => 'TND',
                    'amount' => '100.000',
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
                ],
            ],
        ]);

        $this->assertCount(3, $invoice->invoice_amounts);
        
        // Find the total TTC amount
        $ttcAmount = collect($invoice->invoice_amounts)
            ->firstWhere('amount_type_code', 'I-180');
        
        $this->assertEquals('119.000', $ttcAmount['amount']);
        $this->assertEquals('CENT DIX NEUF DINARS', $ttcAmount['description']);
    }

    public function test_parse_teif_xml_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $xmlContent = file_get_contents(base_path('tests/fixtures/test_invoice.xml'));
        $file = UploadedFile::fake()->createWithContent('test.xml', $xmlContent);

        $response = $this->postJson('/invoices-import/parse', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');
        $this->assertEquals('INV-TEST-001', $data['invoice_id']);
        $this->assertEquals('I-11', $data['invoice_type']);
        $this->assertEquals('2026-04-03', $data['issue_date']);
        $this->assertEquals('Test Seller Company', $data['sender_name']);
        $this->assertEquals('1234567ABC000', $data['sender_tax_id']);
        $this->assertEquals('Test Buyer Customer', $data['receiver_name']);
        $this->assertEquals('9876543XYZ000', $data['receiver_tax_id']);
        $this->assertEquals('125.000', $data['total_ht']);
        $this->assertEquals('23.750', $data['total_tva']);
        $this->assertEquals('148.750', $data['total_ttc']);
        $this->assertCount(2, $data['lines']);
    }

    public function test_import_teif_xml_creates_invoice_and_related_records(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $parsedData = [
            'invoice_id' => 'INV-TEST-002',
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

        $response = $this->postJson('/invoices-import/store', $parsedData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Invoice imported successfully.',
        ]);

        // Verify invoice was created
        $this->assertDatabaseHas('invoices', [
            'document_identifier' => 'INV-TEST-002',
            'document_type_code' => 'I-11',
            'sender_identifier' => '1234567ABC000',
            'receiver_identifier' => '9876543XYZ000',
        ]);

        // Verify partners were created
        $this->assertDatabaseHas('invoice_partners', [
            'function_code' => 'I-62',
            'partner_identifier' => '1234567ABC000',
        ]);
        $this->assertDatabaseHas('invoice_partners', [
            'function_code' => 'I-64',
            'partner_identifier' => '9876543XYZ000',
        ]);

        // Verify lines were created
        $this->assertDatabaseHas('invoice_lines', [
            'item_code' => 'PROD001',
            'item_description' => 'Test Product One',
        ]);
        $this->assertDatabaseHas('invoice_lines', [
            'item_code' => 'PROD002',
            'item_description' => 'Test Product Two',
        ]);

        // Verify customer was auto-created
        $this->assertDatabaseHas('customers', [
            'matricule_fiscal' => '9876543XYZ000',
            'name' => 'Test Buyer Customer',
        ]);

        // Verify products were auto-created
        $this->assertDatabaseHas('products', [
            'code' => 'PROD001',
            'name' => 'Test Product One',
        ]);
        $this->assertDatabaseHas('products', [
            'code' => 'PROD002',
            'name' => 'Test Product Two',
        ]);
    }

    public function test_import_xml_rejects_duplicate_invoice_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create an existing invoice
        Invoice::factory()->create([
            'document_identifier' => 'INV-DUPLICATE-001',
        ]);

        $parsedData = [
            'invoice_id' => 'INV-DUPLICATE-001',
            'invoice_type' => 'I-11',
            'issue_date' => '2026-04-03',
            'sender_name' => 'Test Seller',
            'sender_tax_id' => '1234567ABC000',
            'receiver_name' => 'Test Buyer',
            'receiver_tax_id' => '9876543XYZ000',
            'total_ht' => '100.000',
            'total_tva' => '19.000',
            'total_ttc' => '119.000',
            'lines' => [
                [
                    'line_id' => '1',
                    'quantity' => '1.0',
                    'description' => 'Test Product',
                    'unit_price' => '100.000',
                    'line_amount' => '100.000',
                ],
            ],
        ];

        $response = $this->postJson('/invoices-import/store', $parsedData);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Invoice ID already exists in the system.',
        ]);
    }

    public function test_parse_xml_detects_duplicate_invoice_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create an existing invoice with the same ID as in the test XML
        Invoice::factory()->create([
            'document_identifier' => 'INV-TEST-001',
        ]);

        $xmlContent = file_get_contents(base_path('tests/fixtures/test_invoice.xml'));
        $file = UploadedFile::fake()->createWithContent('test.xml', $xmlContent);

        $response = $this->postJson('/invoices-import/parse', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
        ]);

        $errors = $response->json('errors');
        $this->assertContains("Invoice ID 'INV-TEST-001' already exists in the system.", $errors);
    }
}
