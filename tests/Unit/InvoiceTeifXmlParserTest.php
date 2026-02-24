<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TeifXmlParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTeifXmlParserTest extends TestCase
{
    use RefreshDatabase;

    private TeifXmlParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TeifXmlParser();
    }

    public function test_parse_returns_array(): void
    {
        $xml = $this->getSampleTeifXml();
        $result = $this->parser->parse($xml);

        $this->assertIsArray($result);
    }

    public function test_parse_extracts_version_and_controlling_agency(): void
    {
        $xml = $this->getSampleTeifXml();
        $result = $this->parser->parse($xml);

        $this->assertEquals('1.8.8', $result['version']);
        $this->assertEquals('TTN', $result['controlling_agency']);
    }

    public function test_parse_extracts_header(): void
    {
        $xml = $this->getSampleTeifXml();
        $result = $this->parser->parse($xml);

        $this->assertArrayHasKey('header', $result);
        $this->assertEquals('0736202XAM000', $result['header']['sender_identifier']);
        $this->assertEquals('I-01', $result['header']['sender_type']);
        $this->assertEquals('0914089JAM000', $result['header']['receiver_identifier']);
    }

    public function test_parse_extracts_bgm(): void
    {
        $xml = $this->getSampleTeifXml();
        $result = $this->parser->parse($xml);

        $this->assertArrayHasKey('body', $result);
        $this->assertArrayHasKey('bgm', $result['body']);
        $this->assertEquals('FA-2026-0001', $result['body']['bgm']['document_identifier']);
        $this->assertEquals('I-11', $result['body']['bgm']['document_type_code']);
        $this->assertEquals('Facture', $result['body']['bgm']['document_type_name']);
    }

    public function test_parse_extracts_dtm(): void
    {
        $xml = $this->getSampleTeifXml();
        $result = $this->parser->parse($xml);

        $this->assertArrayHasKey('dtm', $result['body']);
        $this->assertNotEmpty($result['body']['dtm']);
        $this->assertEquals('I-31', $result['body']['dtm'][0]['function_code']);
        $this->assertEquals('ddMMyy', $result['body']['dtm'][0]['format']);
        $this->assertEquals('150226', $result['body']['dtm'][0]['value']);
    }

    public function test_parse_extracts_partners(): void
    {
        $xml = $this->getSampleTeifXml();
        $result = $this->parser->parse($xml);

        $this->assertArrayHasKey('partners', $result['body']);
        $this->assertNotEmpty($result['body']['partners']);
        
        $partner = $result['body']['partners'][0];
        $this->assertEquals('I-62', $partner['function_code']);
        $this->assertEquals('0736202XAM000', $partner['partner_identifier']);
        $this->assertEquals('Test Company', $partner['partner_name']);
    }

    public function test_parse_extracts_partner_address(): void
    {
        $xml = $this->getSampleTeifXml();
        $result = $this->parser->parse($xml);

        $partner = $result['body']['partners'][0];
        $this->assertNotEmpty($partner['addresses']);
        $this->assertEquals('Tunis', $partner['addresses'][0]['city']);
        $this->assertEquals('TN', $partner['addresses'][0]['country']);
    }

    public function test_parse_extracts_lines(): void
    {
        $xml = $this->getSampleTeifXml();
        $result = $this->parser->parse($xml);

        $this->assertArrayHasKey('lines', $result['body']);
        $this->assertNotEmpty($result['body']['lines']);
        
        $line = $result['body']['lines'][0];
        $this->assertEquals('1', $line['item_identifier']);
        $this->assertEquals('PROD001', $line['item_code']);
        $this->assertEquals('Test Product', $line['item_description']);
        $this->assertEquals('1.000', $line['quantity']);
        $this->assertEquals('UNIT', $line['measurement_unit']);
    }

    public function test_parse_extracts_line_tax(): void
    {
        $xml = $this->getSampleTeifXml();
        $result = $this->parser->parse($xml);

        $line = $result['body']['lines'][0];
        $this->assertEquals('I-1602', $line['tax_type_code']);
        $this->assertEquals('TVA', $line['tax_type_name']);
        $this->assertEquals('19', $line['tax_rate']);
    }

    public function test_parse_extracts_line_amounts(): void
    {
        $xml = $this->getSampleTeifXml();
        $result = $this->parser->parse($xml);

        $line = $result['body']['lines'][0];
        $this->assertNotEmpty($line['amounts']);
        $this->assertEquals('I-183', $line['amounts'][0]['amount_type_code']);
        $this->assertEquals('100.000', $line['amounts'][0]['amount']);
    }

    public function test_parse_extracts_invoice_amounts(): void
    {
        $xml = $this->getSampleTeifXml();
        $result = $this->parser->parse($xml);

        $this->assertArrayHasKey('invoice_amounts', $result['body']);
        $this->assertNotEmpty($result['body']['invoice_amounts']);
    }

    public function test_parse_extracts_invoice_taxes(): void
    {
        $xml = $this->getSampleTeifXml();
        $result = $this->parser->parse($xml);

        $this->assertArrayHasKey('invoice_taxes', $result['body']);
        $this->assertNotEmpty($result['body']['invoice_taxes']);
        
        $tax = $result['body']['invoice_taxes'][0];
        $this->assertEquals('I-1602', $tax['tax_type_code']);
        $this->assertEquals('TVA', $tax['tax_type_name']);
    }

    public function test_parse_handles_missing_optional_fields(): void
    {
        $xml = $this->getMinimalTeifXml();
        $result = $this->parser->parse($xml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('body', $result);
    }

    public function test_parse_extracts_signatures_if_present(): void
    {
        $xml = $this->getSignedTeifXml();
        $result = $this->parser->parse($xml);

        $this->assertArrayHasKey('signatures', $result);
        $this->assertNotEmpty($result['signatures']);
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

    private function getMinimalTeifXml(): string
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
    <PartnerSection></PartnerSection>
    <LinSection></LinSection>
    <InvoiceMoa></InvoiceMoa>
    <InvoiceTax></InvoiceTax>
  </InvoiceBody>
</TEIF>';
    }

    private function getSignedTeifXml(): string
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
    <PartnerSection></PartnerSection>
    <LinSection></LinSection>
    <InvoiceMoa></InvoiceMoa>
    <InvoiceTax></InvoiceTax>
  </InvoiceBody>
  <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" Id="SigTest">
    <ds:SignatureValue>TestSignatureValue</ds:SignatureValue>
    <ds:KeyInfo>
      <ds:X509Data>
        <ds:X509Certificate>TestCertificate</ds:X509Certificate>
      </ds:X509Data>
    </ds:KeyInfo>
    <ds:Object>
      <xades:QualifyingProperties>
        <xades:SignedProperties>
          <xades:SignedSignatureProperties>
            <xades:SigningTime>2026-02-15T10:00:00Z</xades:SigningTime>
            <xades:SignerRole>
              <xades:ClaimedRoles>
                <xades:ClaimedRole>Fournisseur</xades:ClaimedRole>
              </xades:ClaimedRoles>
            </xades:SignerRole>
          </xades:SignedSignatureProperties>
        </xades:SignedProperties>
      </xades:QualifyingProperties>
    </ds:Object>
  </ds:Signature>
</TEIF>';
    }
}
