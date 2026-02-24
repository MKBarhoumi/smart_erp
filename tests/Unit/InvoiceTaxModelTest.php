<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceTax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTaxModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_tax_can_be_created_with_factory(): void
    {
        $tax = InvoiceTax::factory()->create();

        $this->assertInstanceOf(InvoiceTax::class, $tax);
        $this->assertNotNull($tax->id);
    }

    public function test_invoice_tax_belongs_to_invoice(): void
    {
        $invoice = Invoice::factory()->create();
        $tax = InvoiceTax::factory()->create(['invoice_id' => $invoice->id]);

        $this->assertInstanceOf(Invoice::class, $tax->invoice);
        $this->assertEquals($invoice->id, $tax->invoice->id);
    }

    public function test_invoice_tax_timbre_state(): void
    {
        $tax = InvoiceTax::factory()->timbre()->create();

        $this->assertEquals('I-1601', $tax->tax_type_code);
        $this->assertEquals('droit de timbre', $tax->tax_type_name);
        $this->assertEquals('0', $tax->tax_rate);
    }

    public function test_invoice_tax_amounts_is_cast_to_array(): void
    {
        $tax = InvoiceTax::factory()->create([
            'amounts' => [
                [
                    'amount_type_code' => 'I-177',
                    'currency_identifier' => 'TND',
                    'amount' => '100.000',
                ],
                [
                    'amount_type_code' => 'I-178',
                    'currency_identifier' => 'TND',
                    'amount' => '19.000',
                ],
            ],
        ]);

        $this->assertIsArray($tax->amounts);
        $this->assertCount(2, $tax->amounts);
        $this->assertEquals('I-177', $tax->amounts[0]['amount_type_code']);
    }

    public function test_invoice_tax_has_required_fields(): void
    {
        $tax = InvoiceTax::factory()->create([
            'tax_type_code' => 'I-1602',
            'tax_type_name' => 'TVA',
            'tax_rate' => '19',
        ]);

        $this->assertEquals('I-1602', $tax->tax_type_code);
        $this->assertEquals('TVA', $tax->tax_type_name);
        $this->assertEquals('19', $tax->tax_rate);
    }

    public function test_invoice_tax_category_can_be_nullable(): void
    {
        $tax = InvoiceTax::factory()->create([
            'tax_category' => null,
        ]);

        $this->assertNull($tax->tax_category);
    }
}
