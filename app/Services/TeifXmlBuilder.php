<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AmountTypeCode;
use App\Enums\TaxTypeCode;
use App\Exceptions\TeifValidationException;
use App\Models\CompanySetting;
use App\Models\Invoice;
use DOMDocument;
use DOMElement;

/**
 * Generates TEIF XML v1.8.8 for Tunisia TradeNet El Fatoora.
 */
class TeifXmlBuilder
{
    private DOMDocument $dom;
    private DOMElement $root;

    public function __construct(
        private readonly AmountInWordsService $amountInWords,
    ) {
    }

    /**
     * Build the complete TEIF XML document for an invoice.
     *
     * @throws TeifValidationException
     */
    public function build(Invoice $invoice): string
    {
        $invoice->loadMissing(['customer', 'lines', 'taxLines', 'allowances']);

        $settings = CompanySetting::firstOrFail();

        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;

        $this->root = $this->dom->createElement('TEIF');
        $this->root->setAttribute('controlingAgency', 'TTN');
        $this->root->setAttribute('version', '1.8.8');
        $this->dom->appendChild($this->root);

        $this->buildInvoiceHeader($invoice, $settings);
        $this->buildInvoiceBody($invoice, $settings);

        $xml = $this->dom->saveXML();
        if ($xml === false) {
            throw new TeifValidationException('Failed to generate XML');
        }

        return $xml;
    }

    private function buildInvoiceHeader(Invoice $invoice, CompanySetting $settings): void
    {
        $header = $this->dom->createElement('InvoiceHeader');

        $sender = $this->dom->createElement('MessageSenderIdentifier', $settings->matricule_fiscal);
        $sender->setAttribute('type', 'I-01');
        $header->appendChild($sender);

        $receiver = $this->dom->createElement(
            'MessageRecieverIdentifier',
            $invoice->customer->identifier_value
        );
        $receiver->setAttribute('type', $invoice->customer->identifier_type->value);
        $header->appendChild($receiver);

        $this->root->appendChild($header);
    }

    private function buildInvoiceBody(Invoice $invoice, CompanySetting $settings): void
    {
        $body = $this->dom->createElement('InvoiceBody');

        $this->buildBgm($body, $invoice);
        $this->buildDtm($body, $invoice);
        $this->buildPartnerSection($body, $invoice, $settings);
        $this->buildPytSection($body, $invoice, $settings);
        $this->buildLinSection($body, $invoice);
        $this->buildInvoiceMoa($body, $invoice);
        $this->buildInvoiceTax($body, $invoice);

        $this->root->appendChild($body);
    }

    private function buildBgm(DOMElement $parent, Invoice $invoice): void
    {
        $bgm = $this->dom->createElement('Bgm');

        $docId = $this->dom->createElement('DocumentIdentifier', $invoice->document_identifier);
        $bgm->appendChild($docId);

        $docType = $this->dom->createElement('DocumentType', $invoice->document_type_code->label());
        $docType->setAttribute('code', $invoice->document_type_code->value);
        $bgm->appendChild($docType);

        if ($invoice->parent_invoice_id) {
            $refs = $this->dom->createElement('DocumentReferences');
            $ref = $this->dom->createElement('Reference');
            $refId = $this->dom->createElement('ReferenceIdentifier', $invoice->parentInvoice?->document_identifier ?? '');
            $refId->setAttribute('refID', 'I-87');
            $ref->appendChild($refId);
            $refs->appendChild($ref);
            $bgm->appendChild($refs);
        }

        $parent->appendChild($bgm);
    }

    private function buildDtm(DOMElement $parent, Invoice $invoice): void
    {
        $dtm = $this->dom->createElement('Dtm');

        $invoiceDate = $this->dom->createElement('DateText', $invoice->invoice_date->format('dmy'));
        $invoiceDate->setAttribute('format', 'ddMMyy');
        $invoiceDate->setAttribute('functionCode', 'I-31');
        $dtm->appendChild($invoiceDate);

        if ($invoice->billing_period_start && $invoice->billing_period_end) {
            $period = $this->dom->createElement(
                'DateText',
                $invoice->billing_period_start->format('dmy') . '-' . $invoice->billing_period_end->format('dmy')
            );
            $period->setAttribute('format', 'ddMMyy-ddMMyy');
            $period->setAttribute('functionCode', 'I-36');
            $dtm->appendChild($period);
        }

        if ($invoice->due_date) {
            $dueDate = $this->dom->createElement('DateText', $invoice->due_date->format('dmy'));
            $dueDate->setAttribute('format', 'ddMMyy');
            $dueDate->setAttribute('functionCode', 'I-32');
            $dtm->appendChild($dueDate);
        }

        $parent->appendChild($dtm);
    }

    private function buildPartnerSection(DOMElement $parent, Invoice $invoice, CompanySetting $settings): void
    {
        $section = $this->dom->createElement('PartnerSection');

        // Seller (I-62)
        $this->buildPartner($section, 'I-62', $settings->company_name, $settings->matricule_fiscal, [
            'address_description' => $settings->address_description,
            'street' => $settings->street,
            'city' => $settings->city,
            'postal_code' => $settings->postal_code,
            'country_code' => $settings->country_code,
        ], [
            'I-81' => $settings->matricule_fiscal,
            'I-811' => $settings->category_type,
            'I-812' => $settings->person_type,
            'I-813' => $settings->tax_office,
            'I-815' => $settings->registre_commerce,
            'I-816' => $settings->legal_form,
        ], [
            'I-101' => $settings->phone,
            'I-102' => $settings->fax,
            'I-103' => $settings->email,
            'I-104' => $settings->website,
        ]);

        // Buyer (I-61)
        $customer = $invoice->customer;
        $this->buildPartner($section, 'I-61', $customer->name, $customer->identifier_value, [
            'address_description' => $customer->address_description,
            'street' => $customer->street,
            'city' => $customer->city,
            'postal_code' => $customer->postal_code,
            'country_code' => $customer->country_code,
        ], [
            'I-81' => $customer->matricule_fiscal,
            'I-811' => $customer->category_type,
            'I-812' => $customer->person_type,
            'I-813' => $customer->tax_office,
            'I-815' => $customer->registre_commerce,
            'I-816' => $customer->legal_form,
        ], [
            'I-101' => $customer->phone,
            'I-102' => $customer->fax,
            'I-103' => $customer->email,
            'I-104' => $customer->website,
        ]);

        $parent->appendChild($section);
    }

    /**
     * @param array<string, string|null> $address
     * @param array<string, string|null> $references
     * @param array<string, string|null> $contacts
     */
    private function buildPartner(
        DOMElement $parent,
        string $functionCode,
        string $name,
        string $identifier,
        array $address,
        array $references,
        array $contacts,
    ): void {
        $partner = $this->dom->createElement('Partner');
        $partner->setAttribute('functionCode', $functionCode);

        $nad = $this->dom->createElement('Nad');

        $partnerIdElem = $this->dom->createElement('PartnerIdentifier', $identifier);
        $partnerIdElem->setAttribute('type', 'I-01');
        $nad->appendChild($partnerIdElem);

        $nameElem = $this->dom->createElement('PartnerName');
        $nameElem->setAttribute('nameType', 'Qualification');
        $nameNode = $this->dom->createElement('Name', $name);
        $nameElem->appendChild($nameNode);
        $nad->appendChild($nameElem);

        if (array_filter($address)) {
            $addrElem = $this->dom->createElement('PartnerAdresses');
            foreach (['address_description' => 'Description', 'street' => 'Street', 'city' => 'City', 'postal_code' => 'PostalCode', 'country_code' => 'CountryCode'] as $key => $tag) {
                if (!empty($address[$key])) {
                    $addrElem->appendChild($this->dom->createElement($tag, $address[$key]));
                }
            }
            $nad->appendChild($addrElem);
        }

        $partner->appendChild($nad);

        // References
        $rffSection = $this->dom->createElement('RffSection');
        foreach ($references as $refCode => $refValue) {
            if (!empty($refValue)) {
                $rff = $this->dom->createElement('Rff');
                $refElem = $this->dom->createElement('ReferenceIdentifier', $refValue);
                $refElem->setAttribute('refID', $refCode);
                $rff->appendChild($refElem);
                $rffSection->appendChild($rff);
            }
        }
        if ($rffSection->hasChildNodes()) {
            $partner->appendChild($rffSection);
        }

        // Contact
        $ctaSection = $this->dom->createElement('CtaSection');
        $com = $this->dom->createElement('Com');
        foreach ($contacts as $comCode => $comValue) {
            if (!empty($comValue)) {
                $comMeans = $this->dom->createElement('CommunicationMeans', $comValue);
                $comMeans->setAttribute('communicationType', $comCode);
                $com->appendChild($comMeans);
            }
        }
        if ($com->hasChildNodes()) {
            $ctaSection->appendChild($com);
            $partner->appendChild($ctaSection);
        }

        $parent->appendChild($partner);
    }

    private function buildPytSection(DOMElement $parent, Invoice $invoice, CompanySetting $settings): void
    {
        if (empty($settings->bank_rib) && empty($settings->postal_account)) {
            return;
        }

        $pyt = $this->dom->createElement('PytSection');

        if (!empty($settings->bank_rib)) {
            $pytTerms = $this->dom->createElement('PaymentTerms');
            $pytTerms->setAttribute('code', 'I-114');
            $fiiSection = $this->dom->createElement('FiiSection');
            $fii = $this->dom->createElement('Fii');
            $fii->setAttribute('functionCode', 'I-141');
            $accountNum = $this->dom->createElement('AccountNumber', $settings->bank_rib);
            $fii->appendChild($accountNum);

            if (!empty($settings->bank_name)) {
                $institution = $this->dom->createElement('InstitutionIdentifier', $settings->bank_name);
                $fii->appendChild($institution);
            }
            if (!empty($settings->bank_branch_code)) {
                $branch = $this->dom->createElement('BranchNumber', $settings->bank_branch_code);
                $fii->appendChild($branch);
            }

            $fiiSection->appendChild($fii);
            $pytTerms->appendChild($fiiSection);
            $pyt->appendChild($pytTerms);
        }

        if (!empty($settings->postal_account)) {
            $pytTerms = $this->dom->createElement('PaymentTerms');
            $pytTerms->setAttribute('code', 'I-115');
            $fiiSection = $this->dom->createElement('FiiSection');
            $fii = $this->dom->createElement('Fii');
            $fii->setAttribute('functionCode', 'I-141');
            $accountNum = $this->dom->createElement('AccountNumber', $settings->postal_account);
            $fii->appendChild($accountNum);
            $fiiSection->appendChild($fii);
            $pytTerms->appendChild($fiiSection);
            $pyt->appendChild($pytTerms);
        }

        $parent->appendChild($pyt);
    }

    private function buildLinSection(DOMElement $parent, Invoice $invoice): void
    {
        $linSection = $this->dom->createElement('LinSection');

        foreach ($invoice->lines as $line) {
            $lin = $this->dom->createElement('Lin');

            $lin->appendChild($this->dom->createElement('ItemIdentifier', (string) $line->line_number));

            $imd = $this->dom->createElement('LinImd');
            $imd->setAttribute('lang', $line->item_lang);
            $imd->appendChild($this->dom->createElement('ItemCode', $line->item_code));
            $imd->appendChild($this->dom->createElement('ItemDescription', $line->item_description));
            $lin->appendChild($imd);

            $qty = $this->dom->createElement('LinQty');
            $qtyElem = $this->dom->createElement('Quantity', $this->formatAmount($line->quantity));
            $qtyElem->setAttribute('measurementUnit', $line->unit_of_measure);
            $qty->appendChild($qtyElem);
            $lin->appendChild($qty);

            $tax = $this->dom->createElement('LinTax');
            $taxName = $this->dom->createElement('TaxTypeName', 'TVA');
            $taxName->setAttribute('code', TaxTypeCode::TVA->value);
            $tax->appendChild($taxName);
            $taxDetails = $this->dom->createElement('TaxDetails');
            $taxDetails->appendChild($this->dom->createElement('TaxRate', $this->formatRate($line->tva_rate)));
            $tax->appendChild($taxDetails);
            $lin->appendChild($tax);

            $linMoa = $this->dom->createElement('LinMoa');

            // I-183: Unit price
            $this->appendMoaDetails($linMoa, AmountTypeCode::LINE_UNIT_PRICE->value, $line->unit_price);
            // I-171: Line net amount
            $this->appendMoaDetails($linMoa, AmountTypeCode::LINE_NET->value, $line->line_net_amount);

            $lin->appendChild($linMoa);
            $linSection->appendChild($lin);
        }

        $parent->appendChild($linSection);
    }

    private function buildInvoiceMoa(DOMElement $parent, Invoice $invoice): void
    {
        $invoiceMoa = $this->dom->createElement('InvoiceMoa');

        // I-179: Total gross
        $this->appendAmountDetails($invoiceMoa, AmountTypeCode::TOTAL_GROSS->value, $invoice->total_gross);
        // I-182: Total net before discount
        $this->appendAmountDetails($invoiceMoa, AmountTypeCode::TOTAL_NET_BEFORE_DISC->value, $invoice->total_net_before_disc);
        // I-176: Total HT
        $this->appendAmountDetails($invoiceMoa, AmountTypeCode::TOTAL_HT->value, $invoice->total_ht);
        // I-181: Total TVA
        $this->appendAmountDetails($invoiceMoa, AmountTypeCode::TOTAL_TVA->value, $invoice->total_tva);

        // I-180: Total TTC with amount description in French
        $amountDetails = $this->dom->createElement('AmountDetails');
        $moa = $this->dom->createElement('Moa');
        $moa->setAttribute('amountTypeCode', AmountTypeCode::TOTAL_TTC->value);
        $moa->setAttribute('currencyCodeList', 'ISO_4217');
        $amount = $this->dom->createElement('Amount', $this->formatAmount($invoice->total_ttc));
        $amount->setAttribute('currencyIdentifier', 'TND');
        $moa->appendChild($amount);

        $description = $this->amountInWords->convert($this->formatAmount($invoice->total_ttc));
        $descElem = $this->dom->createElement('AmountDescription', $description);
        $descElem->setAttribute('lang', 'fr');
        $moa->appendChild($descElem);

        $amountDetails->appendChild($moa);
        $invoiceMoa->appendChild($amountDetails);

        $parent->appendChild($invoiceMoa);
    }

    private function buildInvoiceTax(DOMElement $parent, Invoice $invoice): void
    {
        $invoiceTax = $this->dom->createElement('InvoiceTax');

        // Timbre Fiscal
        if (bccomp((string) $invoice->timbre_fiscal, '0.000', 3) > 0) {
            $taxDetails = $this->dom->createElement('InvoiceTaxDetails');
            $tax = $this->dom->createElement('Tax');
            $taxName = $this->dom->createElement('TaxTypeName', TaxTypeCode::DROIT_TIMBRE->label());
            $taxName->setAttribute('code', TaxTypeCode::DROIT_TIMBRE->value);
            $tax->appendChild($taxName);
            $td = $this->dom->createElement('TaxDetails');
            $td->appendChild($this->dom->createElement('TaxRate', '0'));
            $tax->appendChild($td);
            $taxDetails->appendChild($tax);

            $this->appendAmountDetails($taxDetails, AmountTypeCode::TAX_AMOUNT->value, $invoice->timbre_fiscal);
            $invoiceTax->appendChild($taxDetails);
        }

        // TVA by rate
        foreach ($invoice->taxLines as $taxLine) {
            $taxDetails = $this->dom->createElement('InvoiceTaxDetails');
            $tax = $this->dom->createElement('Tax');
            $taxName = $this->dom->createElement('TaxTypeName', $taxLine->tax_type_name);
            $taxName->setAttribute('code', $taxLine->tax_type_code);
            $tax->appendChild($taxName);
            $td = $this->dom->createElement('TaxDetails');
            $td->appendChild($this->dom->createElement('TaxRate', $this->formatRate($taxLine->tax_rate)));
            $tax->appendChild($td);
            $taxDetails->appendChild($tax);

            // I-177: Taxable amount
            $this->appendAmountDetails($taxDetails, AmountTypeCode::TAXABLE_AMOUNT->value, $taxLine->taxable_amount);
            // I-178: Tax amount
            $this->appendAmountDetails($taxDetails, AmountTypeCode::TAX_AMOUNT->value, $taxLine->tax_amount);

            $invoiceTax->appendChild($taxDetails);
        }

        $parent->appendChild($invoiceTax);
    }

    private function appendMoaDetails(DOMElement $parent, string $amountTypeCode, string|int|float $amount): void
    {
        $moaDetails = $this->dom->createElement('MoaDetails');
        $moa = $this->dom->createElement('Moa');
        $moa->setAttribute('amountTypeCode', $amountTypeCode);
        $moa->setAttribute('currencyCodeList', 'ISO_4217');
        $amountElem = $this->dom->createElement('Amount', $this->formatAmount($amount));
        $amountElem->setAttribute('currencyIdentifier', 'TND');
        $moa->appendChild($amountElem);
        $moaDetails->appendChild($moa);
        $parent->appendChild($moaDetails);
    }

    private function appendAmountDetails(DOMElement $parent, string $amountTypeCode, string|int|float $amount): void
    {
        $amountDetails = $this->dom->createElement('AmountDetails');
        $moa = $this->dom->createElement('Moa');
        $moa->setAttribute('amountTypeCode', $amountTypeCode);
        $moa->setAttribute('currencyCodeList', 'ISO_4217');
        $amountElem = $this->dom->createElement('Amount', $this->formatAmount($amount));
        $amountElem->setAttribute('currencyIdentifier', 'TND');
        $moa->appendChild($amountElem);
        $amountDetails->appendChild($moa);
        $parent->appendChild($amountDetails);
    }

    private function formatAmount(string|int|float $amount): string
    {
        return number_format((float) $amount, 3, '.', '');
    }

    private function formatRate(string|int|float $rate): string
    {
        $floatRate = (float) $rate;
        if ($floatRate === floor($floatRate)) {
            return (string) (int) $floatRate;
        }
        return rtrim(rtrim(number_format($floatRate, 2, '.', ''), '0'), '.');
    }
}
