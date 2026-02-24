<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoicePartner;
use App\Models\InvoiceTax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_can_be_created_with_factory(): void
    {
        $invoice = Invoice::factory()->create();

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertNotNull($invoice->id);
        $this->assertEquals('1.8.8', $invoice->version);
        $this->assertEquals('TTN', $invoice->controlling_agency);
    }

    public function test_invoice_has_partners_relationship(): void
    {
        $invoice = Invoice::factory()->create();
        $partners = InvoicePartner::factory()->count(2)->create(['invoice_id' => $invoice->id]);

        $this->assertCount(2, $invoice->partners);
        $this->assertInstanceOf(InvoicePartner::class, $invoice->partners->first());
    }

    public function test_invoice_has_lines_relationship(): void
    {
        $invoice = Invoice::factory()->create();
        $lines = InvoiceLine::factory()->count(3)->create([
            'invoice_id' => $invoice->id,
            'parent_line_id' => null,
        ]);

        $this->assertCount(3, $invoice->lines);
        $this->assertInstanceOf(InvoiceLine::class, $invoice->lines->first());
    }

    public function test_invoice_has_taxes_relationship(): void
    {
        $invoice = Invoice::factory()->create();
        $taxes = InvoiceTax::factory()->count(2)->create(['invoice_id' => $invoice->id]);

        $this->assertCount(2, $invoice->taxes);
        $this->assertInstanceOf(InvoiceTax::class, $invoice->taxes->first());
    }

    public function test_invoice_dates_is_cast_to_array(): void
    {
        $invoice = Invoice::factory()->create([
            'dates' => [
                ['function_code' => 'I-31', 'format' => 'ddMMyy', 'value' => '150226'],
            ],
        ]);

        $this->assertIsArray($invoice->dates);
        $this->assertEquals('I-31', $invoice->dates[0]['function_code']);
    }

    public function test_invoice_invoice_amounts_is_cast_to_array(): void
    {
        $invoice = Invoice::factory()->create();

        $this->assertIsArray($invoice->invoice_amounts);
        $this->assertNotEmpty($invoice->invoice_amounts);
    }

    public function test_invoice_signatures_is_cast_to_array(): void
    {
        $invoice = Invoice::factory()->signed()->create();

        $this->assertIsArray($invoice->signatures);
        $this->assertNotEmpty($invoice->signatures);
        $this->assertEquals('signed', $invoice->status);
    }

    public function test_invoice_can_be_soft_deleted(): void
    {
        $invoice = Invoice::factory()->create();
        $id = $invoice->id;

        $invoice->delete();

        $this->assertSoftDeleted('invoices', ['id' => $id]);
        $this->assertNotNull(Invoice::withTrashed()->find($id));
    }

    public function test_invoice_fillable_attributes(): void
    {
        $data = [
            'version' => '1.8.8',
            'controlling_agency' => 'TTN',
            'sender_identifier' => 'TEST123',
            'sender_type' => 'I-01',
            'receiver_identifier' => 'TEST456',
            'receiver_type' => 'I-01',
            'document_identifier' => 'FA-2026-0001',
            'document_type_code' => 'I-11',
            'document_type_name' => 'Facture',
            'dates' => [['function_code' => 'I-31', 'format' => 'ddMMyy', 'value' => '150226']],
            'payment_section' => [],
            'free_texts' => [],
            'special_conditions' => [],
            'loc_section' => [],
            'invoice_amounts' => [['amount_type_code' => 'I-176', 'amount' => '100.000']],
            'invoice_allowances' => [],
            'signatures' => [],
            'status' => 'draft',
        ];

        $invoice = Invoice::create($data);

        $this->assertEquals('TEST123', $invoice->sender_identifier);
        $this->assertEquals('FA-2026-0001', $invoice->document_identifier);
    }
}
