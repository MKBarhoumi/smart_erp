<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoicePartner;
use App\Models\InvoiceTax;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
