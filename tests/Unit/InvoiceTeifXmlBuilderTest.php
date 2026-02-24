<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoicePartner;
use App\Models\InvoiceTax;
use App\Services\TeifXmlBuilder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTeifXmlBuilderTest extends TestCase
{
    use RefreshDatabase;

    private TeifXmlBuilder $builder;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new TeifXmlBuilder();

        // Create a complete invoice with relationships
        $this->invoice = Invoice::factory()->create([
            'version' => '1.8.8',
            'controlling_agency' => 'TTN',
            'sender_identifier' => '0736202XAM000',
            'sender_type' => 'I-01',
            'receiver_identifier' => '0914089JAM000',
            'receiver_type' => 'I-01',
            'document_identifier' => 'FA-2026-0001',
            'document_type_code' => 'I-11',
            'document_type_name' => 'Facture',
            'dates' => [
                ['function_code' => 'I-31', 'format' => 'ddMMyy', 'value' => '150226'],
            ],
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
            ],
        ]);

        // Add partners
        InvoicePartner::factory()->seller()->create([
            'invoice_id' => $this->invoice->id,
            'partner_identifier' => '0736202XAM000',
            'partner_name' => 'Société Test SARL',
            'city' => 'Tunis',
            'country' => 'TN',
        ]);

        InvoicePartner::factory()->buyer()->create([
            'invoice_id' => $this->invoice->id,
            'partner_identifier' => '0914089JAM000',
            'partner_name' => 'Client Test SA',
            'city' => 'Sfax',
            'country' => 'TN',
        ]);

        // Add line
        InvoiceLine::factory()->create([
            'invoice_id' => $this->invoice->id,
            'item_identifier' => '1',
            'item_code' => 'PROD001',
            'item_description' => 'Test Product',
            'quantity' => '10.000',
            'measurement_unit' => 'UNIT',
            'tax_type_code' => 'I-1602',
            'tax_type_name' => 'TVA',
            'tax_rate' => '19',
            'amounts' => [
                [
                    'amount_type_code' => 'I-183',
                    'currency_code_list' => 'ISO_4217',
                    'currency_identifier' => 'TND',
                    'amount' => '100.000',
                ],
            ],
        ]);

        // Add tax
        InvoiceTax::factory()->create([
            'invoice_id' => $this->invoice->id,
            'tax_type_code' => 'I-1602',
            'tax_type_name' => 'TVA',
            'tax_rate' => '19',
            'amounts' => [
                [
                    'amount_type_code' => 'I-177',
                    'currency_code_list' => 'ISO_4217',
                    'currency_identifier' => 'TND',
                    'amount' => '100.000',
                ],
                [
                    'amount_type_code' => 'I-178',
                    'currency_code_list' => 'ISO_4217',
                    'currency_identifier' => 'TND',
                    'amount' => '19.000',
                ],
            ],
        ]);
    }

    public function test_build_returns_valid_xml_string(): void
    {
        $this->invoice->load(['partners', 'lines', 'taxes']);

        $xml = $this->builder->build($this->invoice);

        $this->assertIsString($xml);
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
    }

    public function test_build_contains_teif_root_with_attributes(): void
    {
        $this->invoice->load(['partners', 'lines', 'taxes']);

        $xml = $this->builder->build($this->invoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $root = $dom->documentElement;
        $this->assertEquals('TEIF', $root->nodeName);
        $this->assertEquals('TTN', $root->getAttribute('controlingAgency'));
        $this->assertEquals('1.8.8', $root->getAttribute('version'));
    }

    public function test_build_contains_invoice_header(): void
    {
        $this->invoice->load(['partners', 'lines', 'taxes']);

        $xml = $this->builder->build($this->invoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $sender = $xpath->query('//InvoiceHeader/MessageSenderIdentifier')->item(0);
        $receiver = $xpath->query('//InvoiceHeader/MessageRecieverIdentifier')->item(0);

        $this->assertNotNull($sender);
        $this->assertEquals('0736202XAM000', $sender->nodeValue);
        $this->assertEquals('I-01', $sender->getAttribute('type'));

        $this->assertNotNull($receiver);
        $this->assertEquals('0914089JAM000', $receiver->nodeValue);
    }

    public function test_build_contains_bgm_section(): void
    {
        $this->invoice->load(['partners', 'lines', 'taxes']);

        $xml = $this->builder->build($this->invoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $docId = $xpath->query('//InvoiceBody/Bgm/DocumentIdentifier')->item(0);
        $docType = $xpath->query('//InvoiceBody/Bgm/DocumentType')->item(0);

        $this->assertNotNull($docId);
        $this->assertEquals('FA-2026-0001', $docId->nodeValue);

        $this->assertNotNull($docType);
        $this->assertEquals('Facture', $docType->nodeValue);
        $this->assertEquals('I-11', $docType->getAttribute('code'));
    }

    public function test_build_contains_dtm_dates(): void
    {
        $this->invoice->load(['partners', 'lines', 'taxes']);

        $xml = $this->builder->build($this->invoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $dateText = $xpath->query('//InvoiceBody/Dtm/DateText')->item(0);

        $this->assertNotNull($dateText);
        $this->assertEquals('150226', $dateText->nodeValue);
        $this->assertEquals('I-31', $dateText->getAttribute('functionCode'));
        $this->assertEquals('ddMMyy', $dateText->getAttribute('format'));
    }

    public function test_build_contains_partner_section(): void
    {
        $this->invoice->load(['partners', 'lines', 'taxes']);

        $xml = $this->builder->build($this->invoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $partners = $xpath->query('//InvoiceBody/PartnerSection/PartnerDetails');

        $this->assertEquals(2, $partners->length);
    }

    public function test_build_contains_lin_section(): void
    {
        $this->invoice->load(['partners', 'lines', 'taxes']);

        $xml = $this->builder->build($this->invoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $lines = $xpath->query('//InvoiceBody/LinSection/Lin');

        $this->assertEquals(1, $lines->length);

        $itemCode = $xpath->query('//LinSection/Lin/LinImd/ItemCode')->item(0);
        $this->assertEquals('PROD001', $itemCode->nodeValue);
    }

    public function test_build_contains_invoice_moa(): void
    {
        $this->invoice->load(['partners', 'lines', 'taxes']);

        $xml = $this->builder->build($this->invoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $amounts = $xpath->query('//InvoiceBody/InvoiceMoa/AmountDetails');

        $this->assertGreaterThan(0, $amounts->length);
    }

    public function test_build_contains_invoice_tax(): void
    {
        $this->invoice->load(['partners', 'lines', 'taxes']);

        $xml = $this->builder->build($this->invoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $taxes = $xpath->query('//InvoiceBody/InvoiceTax/InvoiceTaxDetails');

        $this->assertEquals(1, $taxes->length);
    }

    public function test_build_with_ref_ttn_val(): void
    {
        $this->invoice->update([
            'ref_ttn_id' => 'I-88',
            'ref_ttn_value' => 'TTN-123456',
            'ref_cev' => 'BASE64QRCODE',
            'ref_ttn_dates' => [
                ['function_code' => 'I-33', 'format' => 'ddMMyy', 'value' => '160226'],
            ],
        ]);
        $this->invoice->load(['partners', 'lines', 'taxes']);

        $xml = $this->builder->build($this->invoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $refTtn = $xpath->query('//RefTtnVal/ReferenceTTN')->item(0);

        $this->assertNotNull($refTtn);
        $this->assertEquals('TTN-123456', $refTtn->nodeValue);
        $this->assertEquals('I-88', $refTtn->getAttribute('refID'));
    }
}
