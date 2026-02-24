<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DocumentTypeCode;
use App\Enums\OldInvoiceStatus;
use App\Models\Customer;
use App\Models\OldInvoice;
use App\Models\OldInvoiceLine;
use App\Models\User;
use App\Services\OldInvoiceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OldInvoiceTest extends TestCase
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

    private function createOldInvoice(array $overrides = []): OldInvoice
    {
        $customer = $overrides['customer'] ?? $this->createCustomer();
        $user = $overrides['user'] ?? $this->createUser();
        unset($overrides['customer'], $overrides['user']);

        return OldInvoice::create(array_merge([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'oldinvoice_number' => 'FAC-' . uniqid(),
            'document_identifier' => 'FAC-' . uniqid(),
            'document_type_code' => DocumentTypeCode::FACTURE,
            'oldinvoice_date' => '2025-01-15',
            'due_date' => '2025-02-15',
            'status' => OldInvoiceStatus::DRAFT,
            'total_ht' => '0.000',
            'total_tva' => '0.000',
            'total_ttc' => '0.000',
        ], $overrides));
    }

    public function test_oldinvoice_can_be_created(): void
    {
        $oldinvoice = $this->createOldInvoice([
            'oldinvoice_number' => 'FAC-2025-0001',
            'document_identifier' => 'FAC-2025-0001',
            'total_ht' => '1000.000',
            'total_tva' => '190.000',
            'total_ttc' => '1190.000',
            'timbre_fiscal' => '1.000',
        ]);

        $this->assertDatabaseHas('oldinvoices', [
            'oldinvoice_number' => 'FAC-2025-0001',
            'status' => OldInvoiceStatus::DRAFT->value,
        ]);
    }

    public function test_oldinvoice_status_is_enum(): void
    {
        $oldinvoice = $this->createOldInvoice();

        $this->assertInstanceOf(OldInvoiceStatus::class, $oldinvoice->status);
        $this->assertInstanceOf(DocumentTypeCode::class, $oldinvoice->document_type_code);
    }

    public function test_oldinvoice_belongs_to_customer(): void
    {
        $customer = $this->createCustomer();
        $oldinvoice = $this->createOldInvoice(['customer' => $customer]);

        $this->assertEquals($customer->id, $oldinvoice->customer->id);
        $this->assertEquals('Test Customer', $oldinvoice->customer->name);
    }

    public function test_oldinvoice_has_lines(): void
    {
        $oldinvoice = $this->createOldInvoice([
            'total_ht' => '1000.000',
            'total_tva' => '190.000',
            'total_ttc' => '1190.000',
        ]);

        OldInvoiceLine::create([
            'oldinvoice_id' => $oldinvoice->id,
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

        $oldinvoice->refresh();

        $this->assertCount(1, $oldinvoice->lines);
        $this->assertEquals('Service A', $oldinvoice->lines->first()->item_description);
    }

    public function test_oldinvoice_status_transition_draft_to_validated(): void
    {
        $oldinvoice = $this->createOldInvoice();

        $this->assertTrue($oldinvoice->status->canTransitionTo(OldInvoiceStatus::VALIDATED));

        $oldinvoice->update(['status' => OldInvoiceStatus::VALIDATED]);

        $this->assertEquals(OldInvoiceStatus::VALIDATED, $oldinvoice->fresh()->status);
    }

    public function test_oldinvoice_calculation_with_lines(): void
    {
        $service = new OldInvoiceCalculationService();

        $oldinvoice = $this->createOldInvoice();

        OldInvoiceLine::create([
            'oldinvoice_id' => $oldinvoice->id,
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

        $oldinvoice->refresh();
        $totals = $service->calculateTotals($oldinvoice, '1.000', true);

        $this->assertEquals('500.000', $totals['total_ht']);
        $this->assertEquals('95.000', $totals['total_tva']);
        $this->assertEquals('1.000', $totals['timbre_fiscal']);
        $this->assertEquals('596.000', $totals['total_ttc']);
    }

    public function test_oldinvoice_all_document_types(): void
    {
        $customer = $this->createCustomer();
        $user = $this->createUser();

        foreach (DocumentTypeCode::cases() as $type) {
            $oldinvoice = OldInvoice::create([
                'customer_id' => $customer->id,
                'created_by' => $user->id,
                'oldinvoice_number' => "DOC-{$type->value}-" . uniqid(),
                'document_identifier' => "DOC-{$type->value}-" . uniqid(),
                'document_type_code' => $type,
                'oldinvoice_date' => now(),
                'due_date' => now()->addMonth(),
                'status' => OldInvoiceStatus::DRAFT,
                'total_ht' => '0.000',
                'total_tva' => '0.000',
                'total_ttc' => '0.000',
            ]);

            $this->assertEquals($type, $oldinvoice->document_type_code);
        }
    }
}
