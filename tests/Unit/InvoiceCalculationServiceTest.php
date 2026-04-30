<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Services\InvoiceCalculationService;
use PHPUnit\Framework\TestCase;

class InvoiceCalculationServiceTest extends TestCase
{
    private InvoiceCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceCalculationService();
    }

    public function test_calculate_line_with_discount(): void
    {
        $line = new InvoiceLine([
            'quantity' => '2',
            'tax_rate' => '19',
            'discount_rate' => '10',
            'amounts' => [
                ['amount_type_code' => '212', 'amount' => '100.000']
            ]
        ]);

        $result = $this->service->calculateLine($line);

        // Gross = 2 * 100 = 200
        // Discount = 200 * 10% = 20
        // Net = 200 - 20 = 180
        // TVA = 180 * 19% = 34.2
        
        $this->assertEquals('180.000', $result['line_net_amount']);
        $this->assertEquals('20.000', $result['discount_amount']);
        $this->assertEquals('34.200', $result['tva_amount']);
    }

    public function test_calculate_line_without_discount(): void
    {
        $line = new InvoiceLine([
            'quantity' => '1',
            'tax_rate' => '19',
            'discount_rate' => '0',
            'amounts' => [
                ['amount_type_code' => '212', 'amount' => '50.000']
            ]
        ]);

        $result = $this->service->calculateLine($line);

        $this->assertEquals('50.000', $result['line_net_amount']);
        $this->assertEquals('0.000', $result['discount_amount']);
        $this->assertEquals('9.500', $result['tva_amount']);
    }
}
