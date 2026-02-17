<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DocumentTypeCode;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\User;
use App\Services\InvoiceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;



    private function createCustomer(): Customer
    {
        return Customer::create([
            'name' => 'Test Customer',
            'identifier_type' => 'I-01',
            'identifier_value' => '1234567A/B/M/000',
        ]);
    }

    private function createUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createInvoice(array $overrides = []): Invoice
    {
        $customer = $overrides['customer'] ?? $this->createCustomer();
        $user = $overrides['user'] ?? $this->createUser();
        unset($overrides['customer'], $overrides['user']);

        return Invoice::create(array_merge([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'invoice_number' => 'FAC-' . uniqid(),
            'document_identifier' => 'FAC-' . uniqid(),
            'document_type_code' => DocumentTypeCode::FACTURE,
            'invoice_date' => '2025-01-15',
            'due_date' => '2025-02-15',
            'status' => InvoiceStatus::DRAFT,
            'total_ht' => '0.000',
            'total_tva' => '0.000',
            'total_ttc' => '0.000',
        ], $overrides));
    }

    public function test_invoice_can_be_created(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' => 'FAC-2025-0001',
            'document_identifier' => 'FAC-2025-0001',
            'total_ht' => '1000.000',
            'total_tva' => '190.000',
            'total_ttc' => '1190.000',
            'timbre_fiscal' => '1.000',
        ]);

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'FAC-2025-0001',
            'status' => InvoiceStatus::DRAFT->value,
        ]);
    }

    public function test_invoice_status_is_enum(): void
    {
        $invoice = $this->createInvoice();

        $this->assertInstanceOf(InvoiceStatus::class, $invoice->status);
        $this->assertInstanceOf(DocumentTypeCode::class, $invoice->document_type_code);
    }

    public function test_invoice_belongs_to_customer(): void
    {
        $customer = $this->createCustomer();
        $invoice = $this->createInvoice(['customer' => $customer]);

        $this->assertEquals($customer->id, $invoice->customer->id);
        $this->assertEquals('Test Customer', $invoice->customer->name);
    }

    public function test_invoice_has_lines(): void
    {
        $invoice = $this->createInvoice([
            'total_ht' => '1000.000',
            'total_tva' => '190.000',
            'total_ttc' => '1190.000',
        ]);

        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'item_code' => 'SRV-001',
            'item_description' => 'Service A',
            'quantity' => '2.000',
            'unit_price' => '500.000',
            'tva_rate' => '19.00',
            'discount_rate' => '0.00',
            'line_net_amount' => '1000.000',
            'tva_amount' => '190.000',
            'line_total' => '1190.000',
            'line_number' => 1,
        ]);

        $invoice->refresh();

        $this->assertCount(1, $invoice->lines);
        $this->assertEquals('Service A', $invoice->lines->first()->item_description);
    }

    public function test_invoice_status_transition_draft_to_validated(): void
    {
        $invoice = $this->createInvoice();

        $this->assertTrue($invoice->status->canTransitionTo(InvoiceStatus::VALIDATED));

        $invoice->update(['status' => InvoiceStatus::VALIDATED]);

        $this->assertEquals(InvoiceStatus::VALIDATED, $invoice->fresh()->status);
    }

    public function test_invoice_calculation_with_lines(): void
    {
        $service = new InvoiceCalculationService();

        $invoice = $this->createInvoice();

        InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'item_code' => 'SRV-001',
            'item_description' => 'Service',
            'quantity' => '5.000',
            'unit_price' => '100.000',
            'tva_rate' => '19.00',
            'discount_rate' => '0.00',
            'line_net_amount' => '500.000',
            'tva_amount' => '95.000',
            'line_total' => '595.000',
            'line_number' => 1,
        ]);

        $invoice->refresh();
        $totals = $service->calculateTotals($invoice, '1.000', true);

        $this->assertEquals('500.000', $totals['total_ht']);
        $this->assertEquals('95.000', $totals['total_tva']);
        $this->assertEquals('1.000', $totals['timbre_fiscal']);
        $this->assertEquals('596.000', $totals['total_ttc']);
    }

    public function test_invoice_all_document_types(): void
    {
        $customer = $this->createCustomer();
        $user = $this->createUser();

        foreach (DocumentTypeCode::cases() as $type) {
            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'created_by' => $user->id,
                'invoice_number' => "DOC-{$type->value}-" . uniqid(),
                'document_identifier' => "DOC-{$type->value}-" . uniqid(),
                'document_type_code' => $type,
                'invoice_date' => now(),
                'due_date' => now()->addMonth(),
                'status' => InvoiceStatus::DRAFT,
                'total_ht' => '0.000',
                'total_tva' => '0.000',
                'total_ttc' => '0.000',
            ]);

            $this->assertEquals($type, $invoice->document_type_code);
        }
    }
}
