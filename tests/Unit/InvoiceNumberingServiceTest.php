<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\CompanySetting;
use App\Services\InvoiceNumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberingServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceNumberingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceNumberingService();
    }

    public function test_returns_default_when_no_settings(): void
    {
        $number = $this->service->generateNextNumber();

        $this->assertEquals('INV-0001', $number);
    }

    public function test_generates_number_with_prefix_format(): void
    {
        CompanySetting::create([
            'company_name' => 'Test Co',
            'matricule_fiscal' => '1234567A/B/M/000',
            'category_type' => 'A',
            'person_type' => 'M',
            'city' => 'Tunis',
            'invoice_prefix' => 'FAC',
            'next_invoice_counter' => 1,
            'invoice_number_format' => '{prefix}-{counter}',
        ]);

        $number = $this->service->generateNextNumber();

        $this->assertEquals('FAC-0001', $number);
    }

    public function test_generates_number_with_year_and_month(): void
    {
        CompanySetting::create([
            'company_name' => 'Test Co',
            'matricule_fiscal' => '1234567A/B/M/000',
            'category_type' => 'A',
            'person_type' => 'M',
            'city' => 'Tunis',
            'invoice_prefix' => 'FAC',
            'next_invoice_counter' => 42,
            'invoice_number_format' => '{prefix}/{YYYY}/{MM}/{counter}',
        ]);

        $number = $this->service->generateNextNumber();

        $year = date('Y');
        $month = date('m');
        $this->assertEquals("FAC/{$year}/{$month}/0042", $number);
    }

    public function test_generates_number_with_short_year(): void
    {
        CompanySetting::create([
            'company_name' => 'Test Co',
            'matricule_fiscal' => '1234567A/B/M/000',
            'category_type' => 'A',
            'person_type' => 'M',
            'city' => 'Tunis',
            'invoice_prefix' => 'INV',
            'next_invoice_counter' => 7,
            'invoice_number_format' => '{prefix}-{YY}-{counter}',
        ]);

        $number = $this->service->generateNextNumber();

        $shortYear = date('y');
        $this->assertEquals("INV-{$shortYear}-0007", $number);
    }

    public function test_counter_increments_after_generation(): void
    {
        CompanySetting::create([
            'company_name' => 'Test Co',
            'matricule_fiscal' => '1234567A/B/M/000',
            'category_type' => 'A',
            'person_type' => 'M',
            'city' => 'Tunis',
            'invoice_prefix' => 'FAC',
            'next_invoice_counter' => 1,
            'invoice_number_format' => '{prefix}-{counter}',
        ]);

        $this->service->generateNextNumber();

        $this->assertEquals(2, CompanySetting::first()->next_invoice_counter);
    }

    public function test_sequential_numbers_increment(): void
    {
        CompanySetting::create([
            'company_name' => 'Test Co',
            'matricule_fiscal' => '1234567A/B/M/000',
            'category_type' => 'A',
            'person_type' => 'M',
            'city' => 'Tunis',
            'invoice_prefix' => 'FAC',
            'next_invoice_counter' => 1,
            'invoice_number_format' => '{prefix}-{counter}',
        ]);

        $first = $this->service->generateNextNumber();
        $second = $this->service->generateNextNumber();
        $third = $this->service->generateNextNumber();

        $this->assertEquals('FAC-0001', $first);
        $this->assertEquals('FAC-0002', $second);
        $this->assertEquals('FAC-0003', $third);
    }

    public function test_counter_zero_pads_to_four_digits(): void
    {
        CompanySetting::create([
            'company_name' => 'Test Co',
            'matricule_fiscal' => '1234567A/B/M/000',
            'category_type' => 'A',
            'person_type' => 'M',
            'city' => 'Tunis',
            'invoice_prefix' => 'X',
            'next_invoice_counter' => 999,
            'invoice_number_format' => '{prefix}{counter}',
        ]);

        $this->assertEquals('X0999', $this->service->generateNextNumber());
        $this->assertEquals('X1000', $this->service->generateNextNumber());
    }
}
