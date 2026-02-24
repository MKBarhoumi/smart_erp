<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoicePartner;
use App\Models\InvoiceTax;
use App\Services\InvoiceService;
use App\Services\TeifXmlBuilder;
use App\Services\TeifXmlParser;
use App\Services\TeifXsdValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $service;
    private TeifXmlParser $parser;
    private TeifXmlBuilder $builder;
    private TeifXsdValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new TeifXmlParser();
        $this->builder = new TeifXmlBuilder();
        
        // Mock validator to bypass XSD validation in unit tests
        $this->validator = Mockery::mock(TeifXsdValidator::class);
        $this->validator->shouldReceive('validate')->andReturn(['valid' => true, 'errors' => []]);

        $this->service = new InvoiceService($this->parser, $this->builder, $this->validator);
    }

    public function test_submit_invoice_creates_invoice_from_xml(): void
    {
        $xml = $this->getSampleTeifXml();

        $result = $this->service->submitInvoice($xml, false);

        $this->assertArrayHasKey('invoice_id', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('message', $result);

        $invoice = Invoice::find($result['invoice_id']);
        $this->assertNotNull($invoice);
        $this->assertEquals('draft', $invoice->status);
    }

    public function test_submit_invoice_creates_partners(): void
    {
        $xml = $this->getSampleTeifXml();

        $result = $this->service->submitInvoice($xml, false);
        $invoice = Invoice::with('partners')->find($result['invoice_id']);

        $this->assertNotEmpty($invoice->partners);
    }

    public function test_submit_invoice_creates_lines(): void
    {
        $xml = $this->getSampleTeifXml();

        $result = $this->service->submitInvoice($xml, false);
        $invoice = Invoice::with('lines')->find($result['invoice_id']);

        $this->assertNotEmpty($invoice->lines);
    }

    public function test_submit_invoice_creates_taxes(): void
    {
        $xml = $this->getSampleTeifXml();

        $result = $this->service->submitInvoice($xml, false);
        $invoice = Invoice::with('taxes')->find($result['invoice_id']);

        $this->assertNotEmpty($invoice->taxes);
    }

    public function test_get_invoice_xml_returns_xml_string(): void
    {
        // Create an invoice with all relationships
        $invoice = Invoice::factory()->create();
        InvoicePartner::factory()->seller()->create(['invoice_id' => $invoice->id]);
        InvoicePartner::factory()->buyer()->create(['invoice_id' => $invoice->id]);
        InvoiceLine::factory()->create(['invoice_id' => $invoice->id]);
        InvoiceTax::factory()->create(['invoice_id' => $invoice->id]);

        $result = $this->service->getInvoiceXml($invoice->id);

        $this->assertArrayHasKey('teif_xml', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertStringContainsString('<?xml', $result['teif_xml']);
        $this->assertStringContainsString('TEIF', $result['teif_xml']);
    }

    public function test_list_invoices_returns_paginated_results(): void
    {
        Invoice::factory()->count(25)->create();

        $result = $this->service->listInvoices(['per_page' => 10]);

        $this->assertArrayHasKey('invoices', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(25, $result['total']);
        $this->assertCount(10, $result['invoices']);
    }

    public function test_list_invoices_filters_by_sender_identifier(): void
    {
        Invoice::factory()->count(5)->create(['sender_identifier' => 'SENDER1']);
        Invoice::factory()->count(3)->create(['sender_identifier' => 'SENDER2']);

        $result = $this->service->listInvoices(['sender_identifier' => 'SENDER1']);

        $this->assertEquals(5, $result['total']);
    }

    public function test_list_invoices_filters_by_status(): void
    {
        Invoice::factory()->count(5)->create(['status' => 'draft']);
        Invoice::factory()->count(3)->create(['status' => 'signed']);

        $result = $this->service->listInvoices(['status' => 'signed']);

        $this->assertEquals(3, $result['total']);
    }

    public function test_delete_invoice_soft_deletes(): void
    {
        $invoice = Invoice::factory()->create();

        $result = $this->service->deleteInvoice($invoice->id);

        $this->assertTrue($result['success']);
        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_delete_invoice_throws_for_validated_invoice(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'validated']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot delete a validated invoice.');

        $this->service->deleteInvoice($invoice->id);
    }

    public function test_update_invoice_throws_for_validated_invoice(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'validated']);
        $xml = $this->getSampleTeifXml();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot update a validated invoice.');

        $this->service->updateInvoice($invoice->id, $xml);
    }

    private function getSampleTeifXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<TEIF controlingAgency="TTN" version="1.8.8">
  <InvoiceHeader>
    <MessageSenderIdentifier type="I-01">0736202XAM000</MessageSenderIdentifier>
    <MessageRecieverIdentifier type="I-01">0914089JAM000</MessageRecieverIdentifier>
  </InvoiceHeader>
  <InvoiceBody>
    <Bgm>
      <DocumentIdentifier>FA-2026-0001</DocumentIdentifier>
      <DocumentType code="I-11">Facture</DocumentType>
    </Bgm>
    <Dtm>
      <DateText format="ddMMyy" functionCode="I-31">150226</DateText>
    </Dtm>
    <PartnerSection>
      <PartnerDetails functionCode="I-62">
        <Nad>
          <PartnerIdentifier type="I-01">0736202XAM000</PartnerIdentifier>
          <PartnerName nameType="Qualification">Test Company</PartnerName>
          <PartnerAdresses lang="fr">
            <AdressDescription>Test Address</AdressDescription>
            <Street>Test Street</Street>
            <CityName>Tunis</CityName>
            <PostalCode>1000</PostalCode>
            <Country codeList="ISO_3166-1">TN</Country>
          </PartnerAdresses>
        </Nad>
      </PartnerDetails>
    </PartnerSection>
    <LinSection>
      <Lin>
        <ItemIdentifier>1</ItemIdentifier>
        <LinImd lang="fr">
          <ItemCode>PROD001</ItemCode>
          <ItemDescription>Test Product</ItemDescription>
        </LinImd>
        <LinQty>
          <Quantity measurementUnit="UNIT">1.000</Quantity>
        </LinQty>
        <LinTax>
          <TaxTypeName code="I-1602">TVA</TaxTypeName>
          <TaxDetails>
            <TaxRate>19</TaxRate>
          </TaxDetails>
        </LinTax>
        <LinMoa>
          <MoaDetails>
            <Moa amountTypeCode="I-183" currencyCodeList="ISO_4217">
              <Amount currencyIdentifier="TND">100.000</Amount>
            </Moa>
          </MoaDetails>
        </LinMoa>
      </Lin>
    </LinSection>
    <InvoiceMoa>
      <AmountDetails>
        <Moa amountTypeCode="I-176" currencyCodeList="ISO_4217">
          <Amount currencyIdentifier="TND">100.000</Amount>
        </Moa>
      </AmountDetails>
      <AmountDetails>
        <Moa amountTypeCode="I-180" currencyCodeList="ISO_4217">
          <Amount currencyIdentifier="TND">119.000</Amount>
          <AmountDescription lang="fr">CENT DIX NEUF DINARS</AmountDescription>
        </Moa>
      </AmountDetails>
    </InvoiceMoa>
    <InvoiceTax>
      <InvoiceTaxDetails>
        <Tax>
          <TaxTypeName code="I-1602">TVA</TaxTypeName>
          <TaxDetails>
            <TaxRate>19</TaxRate>
          </TaxDetails>
        </Tax>
        <AmountDetails>
          <Moa amountTypeCode="I-177" currencyCodeList="ISO_4217">
            <Amount currencyIdentifier="TND">100.000</Amount>
          </Moa>
        </AmountDetails>
        <AmountDetails>
          <Moa amountTypeCode="I-178" currencyCodeList="ISO_4217">
            <Amount currencyIdentifier="TND">19.000</Amount>
          </Moa>
        </AmountDetails>
      </InvoiceTaxDetails>
    </InvoiceTax>
  </InvoiceBody>
</TEIF>';
    }
}
