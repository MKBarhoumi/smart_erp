<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoicePartner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePartnerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_partner_can_be_created_with_factory(): void
    {
        $partner = InvoicePartner::factory()->create();

        $this->assertInstanceOf(InvoicePartner::class, $partner);
        $this->assertNotNull($partner->id);
    }

    public function test_invoice_partner_belongs_to_invoice(): void
    {
        $invoice = Invoice::factory()->create();
        $partner = InvoicePartner::factory()->create(['invoice_id' => $invoice->id]);

        $this->assertInstanceOf(Invoice::class, $partner->invoice);
        $this->assertEquals($invoice->id, $partner->invoice->id);
    }

    public function test_invoice_partner_seller_state(): void
    {
        $partner = InvoicePartner::factory()->seller()->create();

        $this->assertEquals('I-61', $partner->function_code);
    }

    public function test_invoice_partner_buyer_state(): void
    {
        $partner = InvoicePartner::factory()->buyer()->create();

        $this->assertEquals('I-62', $partner->function_code);
    }

    public function test_invoice_partner_locations_is_cast_to_array(): void
    {
        $partner = InvoicePartner::factory()->create([
            'locations' => [
                ['function_code' => 'I-91', 'value' => 'Location1'],
            ],
        ]);

        $this->assertIsArray($partner->locations);
    }

    public function test_invoice_partner_references_is_cast_to_array(): void
    {
        $partner = InvoicePartner::factory()->create([
            'references' => [
                ['ref_id' => 'I-815', 'value' => 'B123456789'],
            ],
        ]);

        $this->assertIsArray($partner->references);
        $this->assertEquals('I-815', $partner->references[0]['ref_id']);
    }

    public function test_invoice_partner_contacts_is_cast_to_array(): void
    {
        $partner = InvoicePartner::factory()->create([
            'contacts' => [
                [
                    'function_code' => 'I-94',
                    'contact_identifier' => 'CONT1',
                    'contact_name' => 'John Doe',
                    'com_means_type' => 'I-101',
                    'com_address' => '71 123 456',
                ],
            ],
        ]);

        $this->assertIsArray($partner->contacts);
        $this->assertEquals('John Doe', $partner->contacts[0]['contact_name']);
    }

    public function test_invoice_partner_has_required_fields(): void
    {
        $partner = InvoicePartner::factory()->create([
            'function_code' => 'I-62',
            'partner_identifier' => '0736202XAM000',
            'partner_identifier_type' => 'I-01',
            'partner_name' => 'Test Company',
        ]);

        $this->assertEquals('I-62', $partner->function_code);
        $this->assertEquals('0736202XAM000', $partner->partner_identifier);
        $this->assertEquals('Test Company', $partner->partner_name);
    }
}
