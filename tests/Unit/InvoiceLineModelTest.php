<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceLineModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_line_can_be_created_with_factory(): void
    {
        $line = InvoiceLine::factory()->create();

        $this->assertInstanceOf(InvoiceLine::class, $line);
        $this->assertNotNull($line->id);
    }

    public function test_invoice_line_belongs_to_invoice(): void
    {
        $invoice = Invoice::factory()->create();
        $line = InvoiceLine::factory()->create(['invoice_id' => $invoice->id]);

        $this->assertInstanceOf(Invoice::class, $line->invoice);
        $this->assertEquals($invoice->id, $line->invoice->id);
    }

    public function test_invoice_line_can_have_sub_lines(): void
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

        $this->assertCount(2, $parentLine->subLines);
        $this->assertEquals($parentLine->id, $subLines->first()->parentLine->id);
    }

    public function test_invoice_line_amounts_is_cast_to_array(): void
    {
        $line = InvoiceLine::factory()->create([
            'amounts' => [
                [
                    'amount_type_code' => 'I-183',
                    'currency_identifier' => 'TND',
                    'amount' => '50.000',
                ],
            ],
        ]);

        $this->assertIsArray($line->amounts);
        $this->assertEquals('I-183', $line->amounts[0]['amount_type_code']);
    }

    public function test_invoice_line_api_details_is_cast_to_array(): void
    {
        $line = InvoiceLine::factory()->create([
            'api_details' => [
                ['lang' => 'fr', 'code' => 'API1', 'description' => 'Test API'],
            ],
        ]);

        $this->assertIsArray($line->api_details);
        $this->assertEquals('API1', $line->api_details[0]['code']);
    }

    public function test_invoice_line_allowances_is_cast_to_array(): void
    {
        $line = InvoiceLine::factory()->create([
            'allowances' => [
                ['allowance_code' => 'A01', 'percentage' => '10'],
            ],
        ]);

        $this->assertIsArray($line->allowances);
    }

    public function test_invoice_line_has_required_tax_fields(): void
    {
        $line = InvoiceLine::factory()->create([
            'tax_type_code' => 'I-1602',
            'tax_type_name' => 'TVA',
            'tax_rate' => '19',
        ]);

        $this->assertEquals('I-1602', $line->tax_type_code);
        $this->assertEquals('TVA', $line->tax_type_name);
        $this->assertEquals('19', $line->tax_rate);
    }
}
