<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\DocumentTypeCode;
use App\Enums\IdentifierType;
use App\Enums\TaxTypeCode;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\OldInvoice;
use App\Models\OldInvoiceLine;
use App\Models\OldInvoiceTaxLine;
use App\Models\User;
use App\Services\AmountInWordsService;
use App\Services\TeifXmlBuilder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeifXmlBuilderTest extends TestCase
{
    use RefreshDatabase;

    private TeifXmlBuilder $builder;
    private OldInvoice $oldinvoice;
    private CompanySetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new TeifXmlBuilder(new AmountInWordsService());

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->settings = CompanySetting::create([
            'company_name' => 'Société Test SARL',
            'matricule_fiscal' => '0736202XAM000',
            'category_type' => 'A',
            'person_type' => 'M',
            'tax_office' => 'Tunis',
            'registre_commerce' => 'B12345678',
            'legal_form' => 'SARL',
            'address_description' => '10 Rue de la Liberté',
            'street' => 'Rue de la Liberté',
            'city' => 'Tunis',
            'postal_code' => '1000',
            'country_code' => 'TN',
            'phone' => '+21671000000',
            'fax' => '+21671000001',
            'email' => 'contact@test.tn',
            'website' => 'https://www.test.tn',
            'bank_rib' => '07078010011234567890123',
            'bank_name' => 'Banque Nationale',
            'bank_branch_code' => '010',
            'postal_account' => 'CCP1234567',
            'oldinvoice_number_format' => 'FA-{YYYY}-{SEQ}',
            'next_oldinvoice_counter' => 1,
            'default_timbre_fiscal' => '1.000',
        ]);

        $customer = Customer::create([
            'identifier_type' => IdentifierType::MATRICULE_FISCAL,
            'identifier_value' => '0914089JAM000',
            'name' => 'Client Test SA',
            'city' => 'Sfax',
            'country_code' => 'TN',
            'street' => 'Avenue Habib Bourguiba',
            'postal_code' => '3000',
            'matricule_fiscal' => '0914089JAM000',
            'category_type' => 'A',
            'person_type' => 'M',
        ]);

        $this->oldinvoice = OldInvoice::create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'oldinvoice_number' => 'FA-2026-0001',
            'document_identifier' => 'FA-2026-0001',
            'document_type_code' => DocumentTypeCode::FACTURE,
            'status' => 'draft',
            'oldinvoice_date' => '2026-02-13',
            'due_date' => '2026-03-15',
            'total_gross' => '100.000',
            'total_discount' => '0.000',
            'total_net_before_disc' => '100.000',
            'total_ht' => '100.000',
            'total_tva' => '19.000',
            'timbre_fiscal' => '1.000',
            'total_ttc' => '120.000',
        ]);

        OldInvoiceLine::create([
            'oldinvoice_id' => $this->oldinvoice->id,
            'line_number' => 1,
            'item_code' => 'PROD-001',
            'item_description' => 'Produit de test',
            'item_lang' => 'fr',
            'quantity' => '10.000',
            'unit_of_measure' => 'PCE',
            'unit_price' => '10.000',
            'discount_rate' => '0.00',
            'discount_amount' => '0.000',
            'line_net_amount' => '100.000',
            'tva_rate' => '19.00',
            'tva_amount' => '19.000',
            'line_total' => '119.000',
        ]);

        OldInvoiceTaxLine::create([
            'oldinvoice_id' => $this->oldinvoice->id,
            'tax_type_code' => TaxTypeCode::TVA->value,
            'tax_type_name' => 'TVA',
            'tax_rate' => '19.00',
            'taxable_amount' => '100.000',
            'tax_amount' => '19.000',
        ]);
    }

    public function test_build_returns_valid_xml_string(): void
    {
        $xml = $this->builder->build($this->oldinvoice);

        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<?xml', $xml);

        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'Generated XML should be well-formed');
    }

    public function test_root_element_has_correct_attributes(): void
    {
        $xml = $this->builder->build($this->oldinvoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $root = $dom->documentElement;
        $this->assertEquals('TEIF', $root->nodeName);
        $this->assertEquals('TTN', $root->getAttribute('controlingAgency'));
        $this->assertEquals('1.8.8', $root->getAttribute('version'));
    }

    public function test_oldinvoice_header_contains_sender_and_receiver(): void
    {
        $xml = $this->builder->build($this->oldinvoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        // Sender (company)
        $senders = $xpath->query('//OldInvoiceHeader/MessageSenderIdentifier');
        $this->assertGreaterThan(0, $senders->length);
        $this->assertEquals('0736202XAM000', $senders->item(0)->textContent);
        $this->assertEquals('I-01', $senders->item(0)->getAttribute('type'));

        // Receiver (customer)
        $receivers = $xpath->query('//OldInvoiceHeader/MessageRecieverIdentifier');
        $this->assertGreaterThan(0, $receivers->length);
        $this->assertEquals('0914089JAM000', $receivers->item(0)->textContent);
        $this->assertEquals('I-01', $receivers->item(0)->getAttribute('type'));
    }

    public function test_bgm_section_has_document_identifier_and_type(): void
    {
        $xml = $this->builder->build($this->oldinvoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $docIds = $xpath->query('//OldInvoiceBody/Bgm/DocumentIdentifier');
        $this->assertGreaterThan(0, $docIds->length);
        $this->assertEquals('FA-2026-0001', $docIds->item(0)->textContent);

        $docTypes = $xpath->query('//OldInvoiceBody/Bgm/DocumentType');
        $this->assertGreaterThan(0, $docTypes->length);
        $this->assertEquals('I-11', $docTypes->item(0)->getAttribute('code'));
        $this->assertEquals('Facture', $docTypes->item(0)->textContent);
    }

    public function test_dtm_section_has_dates(): void
    {
        $xml = $this->builder->build($this->oldinvoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $oldinvoiceDates = $xpath->query("//OldInvoiceBody/Dtm/DateText[@functionCode='I-31']");
        $this->assertGreaterThan(0, $oldinvoiceDates->length);
        $this->assertEquals('ddMMyy', $oldinvoiceDates->item(0)->getAttribute('format'));
        // 13 Feb 2026 → 130226
        $this->assertEquals('130226', $oldinvoiceDates->item(0)->textContent);

        $dueDates = $xpath->query("//OldInvoiceBody/Dtm/DateText[@functionCode='I-32']");
        $this->assertGreaterThan(0, $dueDates->length);
        // 15 Mar 2026 → 150326
        $this->assertEquals('150326', $dueDates->item(0)->textContent);
    }

    public function test_partner_section_has_seller_and_buyer(): void
    {
        $xml = $this->builder->build($this->oldinvoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        // Seller (I-62)
        $sellers = $xpath->query("//PartnerSection/Partner[@functionCode='I-62']");
        $this->assertEquals(1, $sellers->length);

        $sellerName = $xpath->query("//PartnerSection/Partner[@functionCode='I-62']//PartnerName/Name");
        $this->assertEquals('Société Test SARL', $sellerName->item(0)->textContent);

        // Buyer (I-61)
        $buyers = $xpath->query("//PartnerSection/Partner[@functionCode='I-61']");
        $this->assertEquals(1, $buyers->length);

        $buyerName = $xpath->query("//PartnerSection/Partner[@functionCode='I-61']//PartnerName/Name");
        $this->assertEquals('Client Test SA', $buyerName->item(0)->textContent);
    }

    public function test_lin_section_has_line_items(): void
    {
        $xml = $this->builder->build($this->oldinvoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $lines = $xpath->query('//LinSection/Lin');
        $this->assertEquals(1, $lines->length);

        $itemCodes = $xpath->query('//LinSection/Lin/LinImd/ItemCode');
        $this->assertEquals('PROD-001', $itemCodes->item(0)->textContent);

        $itemDescs = $xpath->query('//LinSection/Lin/LinImd/ItemDescription');
        $this->assertEquals('Produit de test', $itemDescs->item(0)->textContent);

        $quantities = $xpath->query('//LinSection/Lin/LinQty/Quantity');
        $this->assertEquals('10.000', $quantities->item(0)->textContent);
        $this->assertEquals('PCE', $quantities->item(0)->getAttribute('measurementUnit'));
    }

    public function test_oldinvoice_moa_has_totals(): void
    {
        $xml = $this->builder->build($this->oldinvoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        // Total HT (I-176)
        $totalHt = $xpath->query("//OldInvoiceMoa/AmountDetails/Moa[@amountTypeCode='I-176']/Amount");
        $this->assertGreaterThan(0, $totalHt->length);
        $this->assertEquals('100.000', $totalHt->item(0)->textContent);
        $this->assertEquals('TND', $totalHt->item(0)->getAttribute('currencyIdentifier'));

        // Total TTC (I-180) with AmountDescription
        $totalTtc = $xpath->query("//OldInvoiceMoa/AmountDetails/Moa[@amountTypeCode='I-180']/Amount");
        $this->assertGreaterThan(0, $totalTtc->length);
        $this->assertEquals('120.000', $totalTtc->item(0)->textContent);

        $amountDesc = $xpath->query("//OldInvoiceMoa/AmountDetails/Moa[@amountTypeCode='I-180']/AmountDescription");
        $this->assertGreaterThan(0, $amountDesc->length);
        $this->assertEquals('fr', $amountDesc->item(0)->getAttribute('lang'));
        // Amount description should be in French words
        $this->assertNotEmpty($amountDesc->item(0)->textContent);
    }

    public function test_oldinvoice_tax_has_timbre_and_tva(): void
    {
        $xml = $this->builder->build($this->oldinvoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        // Timbre Fiscal
        $timbreNodes = $xpath->query("//OldInvoiceTax/OldInvoiceTaxDetails/Tax/TaxTypeName[@code='I-1601']");
        $this->assertGreaterThan(0, $timbreNodes->length);

        // TVA
        $tvaNodes = $xpath->query("//OldInvoiceTax/OldInvoiceTaxDetails/Tax/TaxTypeName[@code='I-1602']");
        $this->assertGreaterThan(0, $tvaNodes->length);
    }

    public function test_amounts_have_three_decimal_places(): void
    {
        $xml = $this->builder->build($this->oldinvoice);

        // All Amount elements should have 3 decimal places
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $amounts = $xpath->query('//Amount');
        for ($i = 0; $i < $amounts->length; $i++) {
            $value = $amounts->item($i)->textContent;
            $this->assertMatchesRegularExpression('/^\d+\.\d{3}$/', $value, "Amount '{$value}' should have exactly 3 decimal places");
        }
    }

    public function test_currency_attributes_are_correct(): void
    {
        $xml = $this->builder->build($this->oldinvoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $moas = $xpath->query('//Moa');
        for ($i = 0; $i < $moas->length; $i++) {
            $moa = $moas->item($i);
            $this->assertEquals('ISO_4217', $moa->getAttribute('currencyCodeList'));
        }

        $amounts = $xpath->query('//Amount');
        for ($i = 0; $i < $amounts->length; $i++) {
            $this->assertEquals('TND', $amounts->item($i)->getAttribute('currencyIdentifier'));
        }
    }

    public function test_pyt_section_has_bank_info(): void
    {
        $xml = $this->builder->build($this->oldinvoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $bankAccounts = $xpath->query("//PytSection/PaymentTerms[@code='I-114']//AccountNumber");
        $this->assertGreaterThan(0, $bankAccounts->length);
        $this->assertEquals('07078010011234567890123', $bankAccounts->item(0)->textContent);

        $postalAccounts = $xpath->query("//PytSection/PaymentTerms[@code='I-115']//AccountNumber");
        $this->assertGreaterThan(0, $postalAccounts->length);
        $this->assertEquals('CCP1234567', $postalAccounts->item(0)->textContent);
    }

    public function test_multiple_lines_generates_multiple_lin_elements(): void
    {
        OldInvoiceLine::create([
            'oldinvoice_id' => $this->oldinvoice->id,
            'line_number' => 2,
            'item_code' => 'SRV-001',
            'item_description' => 'Service de consultation',
            'item_lang' => 'fr',
            'quantity' => '5.000',
            'unit_of_measure' => 'HUR',
            'unit_price' => '50.000',
            'discount_rate' => '0.00',
            'discount_amount' => '0.000',
            'line_net_amount' => '250.000',
            'tva_rate' => '7.00',
            'tva_amount' => '17.500',
            'line_total' => '267.500',
        ]);

        // Refresh to reload relationships
        $this->oldinvoice->refresh();
        $this->oldinvoice->unsetRelation('lines');

        $xml = $this->builder->build($this->oldinvoice);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);

        $lines = $xpath->query('//LinSection/Lin');
        $this->assertEquals(2, $lines->length);
    }

    public function test_xml_is_well_formed_and_parseable(): void
    {
        $xml = $this->builder->build($this->oldinvoice);

        $dom = new DOMDocument();
        $loaded = @$dom->loadXML($xml);
        $this->assertTrue($loaded);

        // Verify it has the essential sections
        $this->assertStringContainsString('OldInvoiceHeader', $xml);
        $this->assertStringContainsString('OldInvoiceBody', $xml);
        $this->assertStringContainsString('Bgm', $xml);
        $this->assertStringContainsString('Dtm', $xml);
        $this->assertStringContainsString('PartnerSection', $xml);
        $this->assertStringContainsString('LinSection', $xml);
        $this->assertStringContainsString('OldInvoiceMoa', $xml);
        $this->assertStringContainsString('OldInvoiceTax', $xml);
    }
}
