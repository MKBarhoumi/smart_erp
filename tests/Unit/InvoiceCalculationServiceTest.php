<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\DocumentTypeCode;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\User;
use App\Services\InvoiceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceCalculationService();
    }

    private function createInvoice(): Invoice
    {
        $customer = Customer::create([
            'name' => 'Test Co',
            'identifier_type' => 'I-01',
            'identifier_value' => '1234567A/B/M/000',
        ]);
        $user = User::factory()->create();

        return Invoice::create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'invoice_number' => 'FAC-' . uniqid(),
            'document_identifier' => 'FAC-' . uniqid(),
            'document_type_code' => DocumentTypeCode::FACTURE,
            'invoice_date' => now(),
            'status' => InvoiceStatus::DRAFT,
            'total_ht' => '0.000',
            'total_tva' => '0.000',
            'total_ttc' => '0.000',
        ]);
    }

    public function test_calculate_line_basic(): void
    {
        $invoice = $this->createInvoice();
        $line = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'item_code' => 'PRD-01',
            'item_description' => 'Test',
            'quantity' => '10.000',
            'unit_price' => '25.500',
            'discount_rate' => '0.00',
            'tva_rate' => '19.00',
            'line_number' => 1,
        ]);

        $result = $this->service->calculateLine($line);

        $this->assertEquals('255.000', $result['line_net_amount']);
        $this->assertEquals('48.450', $result['tva_amount']);
        $this->assertEquals('0.000', $result['discount_amount']);
    }

    public function test_calculate_line_with_discount(): void
    {
        $invoice = $this->createInvoice();
        $line = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'item_code' => 'PRD-02',
            'item_description' => 'Discounted',
            'quantity' => '5.000',
            'unit_price' => '100.000',
            'discount_rate' => '10.00',
            'tva_rate' => '19.00',
            'line_number' => 1,
        ]);

        $result = $this->service->calculateLine($line);

        // Gross = 500.000, Discount = 50.000, Net = 450.000
        $this->assertEquals('450.000', $result['line_net_amount']);
        $this->assertEquals('50.000', $result['discount_amount']);
        $this->assertEquals('85.500', $result['tva_amount']);
    }

    public function test_calculate_line_zero_tva(): void
    {
        $invoice = $this->createInvoice();
        $line = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'item_code' => 'PRD-03',
            'item_description' => 'No TVA',
            'quantity' => '1.000',
            'unit_price' => '1000.000',
            'discount_rate' => '0.00',
            'tva_rate' => '0.00',
            'line_number' => 1,
        ]);

        $result = $this->service->calculateLine($line);

        $this->assertEquals('1000.000', $result['line_net_amount']);
        $this->assertEquals('0.000', $result['tva_amount']);
    }

    public function test_calculate_totals_single_line(): void
    {
        $invoice = $this->createInvoice();
        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'item_code' => 'PRD-04',
            'item_description' => 'Single',
            'quantity' => '10.000',
            'unit_price' => '100.000',
            'discount_rate' => '0.00',
            'tva_rate' => '19.00',
            'line_number' => 1,
        ]);

        $invoice->refresh();
        $result = $this->service->calculateTotals($invoice, '1.000', true);

        $this->assertEquals('1000.000', $result['total_ht']);
        $this->assertEquals('190.000', $result['total_tva']);
        $this->assertEquals('1.000', $result['timbre_fiscal']);
        $this->assertEquals('1191.000', $result['total_ttc']);
    }

    public function test_calculate_totals_multiple_lines_different_tva(): void
    {
        $invoice = $this->createInvoice();
        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'item_code' => 'PRD-05',
            'item_description' => 'TVA 19',
            'quantity' => '10.000',
            'unit_price' => '100.000',
            'discount_rate' => '0.00',
            'tva_rate' => '19.00',
            'line_number' => 1,
        ]);
        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'item_code' => 'PRD-06',
            'item_description' => 'TVA 7',
            'quantity' => '5.000',
            'unit_price' => '200.000',
            'discount_rate' => '0.00',
            'tva_rate' => '7.00',
            'line_number' => 2,
        ]);

        $invoice->refresh();
        $result = $this->service->calculateTotals($invoice, '1.000', true);

        $this->assertEquals('2000.000', $result['total_ht']);
        $this->assertEquals('260.000', $result['total_tva']);
        $this->assertEquals('2261.000', $result['total_ttc']);
        $this->assertCount(2, $result['tax_summary']);
    }

    public function test_calculate_totals_no_timbre(): void
    {
        $invoice = $this->createInvoice();
        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'item_code' => 'PRD-07',
            'item_description' => 'No timbre',
            'quantity' => '1.000',
            'unit_price' => '50.000',
            'discount_rate' => '0.00',
            'tva_rate' => '19.00',
            'line_number' => 1,
        ]);

        $invoice->refresh();
        $result = $this->service->calculateTotals($invoice, '0');

        $this->assertEquals('50.000', $result['total_ht']);
        $this->assertEquals('9.500', $result['total_tva']);
        $this->assertEquals('0.000', $result['timbre_fiscal']);
        $this->assertEquals('59.500', $result['total_ttc']);
    }

    public function test_format_tnd_precision(): void
    {
        $this->assertEquals('1234.567', $this->service->formatTND('1234.567'));
        $this->assertEquals('1234.560', $this->service->formatTND('1234.56'));
        $this->assertEquals('0.000', $this->service->formatTND('0'));
        $this->assertEquals('999999.999', $this->service->formatTND('999999.999'));
    }

    public function test_bcmath_precision_no_float_drift(): void
    {
        $invoice = $this->createInvoice();
        $line = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'item_code' => 'PRD-08',
            'item_description' => 'Precision',
            'quantity' => '0.100',
            'unit_price' => '0.200',
            'discount_rate' => '0.00',
            'tva_rate' => '19.00',
            'line_number' => 1,
        ]);

        $result = $this->service->calculateLine($line);

        $this->assertEquals('0.020', $result['line_net_amount']);
        $this->assertEquals('0.003', $result['tva_amount']);
    }
}
