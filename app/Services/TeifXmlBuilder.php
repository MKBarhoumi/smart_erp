<?php

namespace App\Services;

use App\Models\Invoice;
use DOMDocument;
use DOMElement;

class TeifXmlBuilder
{
    private DOMDocument $dom;
    private string $dsNs = 'http://www.w3.org/2000/09/xmldsig#';

    public function build(Invoice $invoice): string
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->formatOutput = true;

        $root = $this->dom->createElement('TEIF');
        $root->setAttribute('controlingAgency', $invoice->controlling_agency);
        $root->setAttribute('version', $invoice->version);
        $this->dom->appendChild($root);

        // InvoiceHeader
        $root->appendChild($this->buildHeader($invoice));

        // InvoiceBody
        $root->appendChild($this->buildBody($invoice));

        // RefTtnVal (if present)
        if ($invoice->ref_ttn_value) {
            $root->appendChild($this->buildRefTtnVal($invoice));
        }

        // Signatures (if present)
        if (!empty($invoice->signatures)) {
            foreach ($invoice->signatures as $sig) {
                $root->appendChild($this->buildSignatureStub($sig));
            }
        }

        return $this->dom->saveXML();
    }

    private function buildHeader(Invoice $invoice): DOMElement
    {
        $header = $this->dom->createElement('InvoiceHeader');

        $sender = $this->dom->createElement('MessageSenderIdentifier',
                    htmlspecialchars($invoice->sender_identifier));
        $sender->setAttribute('type', $invoice->sender_type);
        $header->appendChild($sender);

        $receiver = $this->dom->createElement('MessageRecieverIdentifier',
                    htmlspecialchars($invoice->receiver_identifier));
        if ($invoice->receiver_type) {
            $receiver->setAttribute('type', $invoice->receiver_type);
        }
        $header->appendChild($receiver);

        return $header;
    }

    private function buildBody(Invoice $invoice): DOMElement
    {
        $body = $this->dom->createElement('InvoiceBody');

        // BGM
        $bgm = $this->dom->createElement('Bgm');
        $docId = $this->dom->createElement('DocumentIdentifier',
                   htmlspecialchars($invoice->document_identifier));
        $bgm->appendChild($docId);

        $docType = $this->dom->createElement('DocumentType',
                     htmlspecialchars($invoice->document_type_name));
        if ($invoice->document_type_code) {
            $docType->setAttribute('code', $invoice->document_type_code);
        }
        $bgm->appendChild($docType);
        $body->appendChild($bgm);

        // DTM
        if (!empty($invoice->dates)) {
            $dtm = $this->dom->createElement('Dtm');
            foreach ($invoice->dates as $d) {
                $dateText = $this->dom->createElement('DateText',
                              htmlspecialchars($d['value']));
                $dateText->setAttribute('functionCode', $d['function_code']);
                $dateText->setAttribute('format', $d['format']);
                $dtm->appendChild($dateText);
            }
            $body->appendChild($dtm);
        }

        // PartnerSection
        $body->appendChild($this->buildPartnerSection($invoice));

        // PytSection
        if (!empty($invoice->payment_section)) {
            $body->appendChild($this->buildPaymentSection($invoice->payment_section));
        }

        // LinSection
        $body->appendChild($this->buildLinSection($invoice));

        // InvoiceMoa
        $body->appendChild($this->buildInvoiceMoa($invoice->invoice_amounts));

        // InvoiceTax
        $body->appendChild($this->buildInvoiceTax($invoice));

        return $body;
    }

    private function buildPartnerSection(Invoice $invoice): DOMElement
    {
        $partnerSection = $this->dom->createElement('PartnerSection');
        foreach ($invoice->partners as $partner) {
            $pd = $this->dom->createElement('PartnerDetails');
            $pd->setAttribute('functionCode', $partner->function_code);

            $nad = $this->dom->createElement('Nad');
            $pi = $this->dom->createElement('PartnerIdentifier',
                    htmlspecialchars($partner->partner_identifier));
            $pi->setAttribute('type', $partner->partner_identifier_type);
            $nad->appendChild($pi);

            if ($partner->partner_name) {
                $pn = $this->dom->createElement('PartnerName',
                        htmlspecialchars($partner->partner_name));
                $pn->setAttribute('nameType', $partner->partner_name_type ?? 'Qualification');
                $nad->appendChild($pn);
            }

            if ($partner->address_description) {
                $addr = $this->dom->createElement('PartnerAdresses');
                if ($partner->address_lang) {
                    $addr->setAttribute('lang', $partner->address_lang);
                }
                $this->appendTextChild($addr, 'AdressDescription', $partner->address_description);
                if ($partner->street) $this->appendTextChild($addr, 'Street', $partner->street);
                if ($partner->city)   $this->appendTextChild($addr, 'CityName', $partner->city);
                if ($partner->postal_code) $this->appendTextChild($addr, 'PostalCode', $partner->postal_code);

                $country = $this->dom->createElement('Country',
                             htmlspecialchars($partner->country ?? ''));
                if ($partner->country_code_list) {
                    $country->setAttribute('codeList', $partner->country_code_list);
                }
                $addr->appendChild($country);
                $nad->appendChild($addr);
            }

            $pd->appendChild($nad);

            // References
            if (!empty($partner->references)) {
                foreach ($partner->references as $ref) {
                    $rff = $this->dom->createElement('RffSection');
                    $refEl = $this->dom->createElement('Reference',
                               htmlspecialchars($ref['value']));
                    $refEl->setAttribute('refID', $ref['ref_id']);
                    $rff->appendChild($refEl);
                    $pd->appendChild($rff);
                }
            }

            // Contacts
            if (!empty($partner->contacts)) {
                foreach ($partner->contacts as $ctaData) {
                    $cta = $this->dom->createElement('CtaSection');
                    $contact = $this->dom->createElement('Contact');
                    if ($ctaData['function_code']) {
                        $contact->setAttribute('functionCode', $ctaData['function_code']);
                    }
                    $this->appendTextChild($contact, 'ContactIdentifier',
                        $ctaData['contact_identifier']);
                    $this->appendTextChild($contact, 'ContactName',
                        $ctaData['contact_name']);
                    $cta->appendChild($contact);

                    if (!empty($ctaData['com_means_type'])) {
                        $comm = $this->dom->createElement('Communication');
                        $this->appendTextChild($comm, 'ComMeansType',
                            $ctaData['com_means_type']);
                        $this->appendTextChild($comm, 'ComAdress',
                            $ctaData['com_address'] ?? '');
                        $cta->appendChild($comm);
                    }
                    $pd->appendChild($cta);
                }
            }

            $partnerSection->appendChild($pd);
        }
        return $partnerSection;
    }

    private function buildPaymentSection(array $paymentData): DOMElement
    {
        $pytSection = $this->dom->createElement('PytSection');
        foreach ($paymentData as $psd) {
            $psdEl = $this->dom->createElement('PytSectionDetails');

            if (!empty($psd['payment_terms_type_code'])) {
                $pyt = $this->dom->createElement('Pyt');
                $this->appendTextChild($pyt, 'PaymentTearmsTypeCode',
                    $psd['payment_terms_type_code']);
                if (!empty($psd['payment_terms_description'])) {
                    $this->appendTextChild($pyt, 'PaymentTearmsDescription',
                        $psd['payment_terms_description']);
                }
                $psdEl->appendChild($pyt);
            }

            if (!empty($psd['fii'])) {
                $fii = $this->dom->createElementNS(null, 'PytFii');
                $fii->setAttribute('functionCode', $psd['fii']['function_code']);

                if (!empty($psd['fii']['account_number'])) {
                    $ah = $this->dom->createElement('AccountHolder');
                    $this->appendTextChild($ah, 'AccountNumber',
                        $psd['fii']['account_number']);
                    if (!empty($psd['fii']['owner_identifier'])) {
                        $this->appendTextChild($ah, 'OwnerIdentifier',
                            $psd['fii']['owner_identifier']);
                    }
                    $fii->appendChild($ah);
                }

                if (!empty($psd['fii']['name_code'])) {
                    $inst = $this->dom->createElement('InstitutionIdentification');
                    $inst->setAttribute('nameCode', $psd['fii']['name_code']);
                    if (!empty($psd['fii']['branch_identifier'])) {
                        $this->appendTextChild($inst, 'BranchIdentifier',
                            $psd['fii']['branch_identifier']);
                    }
                    if (!empty($psd['fii']['institution_name'])) {
                        $this->appendTextChild($inst, 'InstitutionName',
                            $psd['fii']['institution_name']);
                    }
                    $fii->appendChild($inst);
                }
                $psdEl->appendChild($fii);
            }

            $pytSection->appendChild($psdEl);
        }
        return $pytSection;
    }

    private function buildLinSection(Invoice $invoice): DOMElement
    {
        $linSection = $this->dom->createElement('LinSection');
        foreach ($invoice->lines as $line) {
            $linSection->appendChild($this->buildOneLin($line->toArray()));
        }
        return $linSection;
    }

    private function buildOneLin(array $line): DOMElement
    {
        $lin = $this->dom->createElement('Lin');
        $this->appendTextChild($lin, 'ItemIdentifier', $line['item_identifier']);

        $imd = $this->dom->createElement('LinImd');
        if (!empty($line['item_lang'])) {
            $imd->setAttribute('lang', $line['item_lang']);
        }
        $this->appendTextChild($imd, 'ItemCode', $line['item_code']);
        $this->appendTextChild($imd, 'ItemDescription', $line['item_description'] ?? '');
        $lin->appendChild($imd);

        // Quantity
        $linQty = $this->dom->createElement('LinQty');
        $qty = $this->dom->createElement('Quantity',
                 htmlspecialchars($line['quantity']));
        $qty->setAttribute('measurementUnit', $line['measurement_unit']);
        $linQty->appendChild($qty);
        $lin->appendChild($linQty);

        // Tax
        $linTax = $this->dom->createElement('LinTax');
        $taxName = $this->dom->createElement('TaxTypeName',
                     htmlspecialchars($line['tax_type_name']));
        $taxName->setAttribute('code', $line['tax_type_code']);
        $linTax->appendChild($taxName);

        $taxDetails = $this->dom->createElement('TaxDetails');
        $this->appendTextChild($taxDetails, 'TaxRate', $line['tax_rate']);
        $linTax->appendChild($taxDetails);
        $lin->appendChild($linTax);

        // LinMoa
        $linMoa = $this->dom->createElement('LinMoa');
        foreach ($line['amounts'] ?? [] as $amt) {
            $linMoa->appendChild($this->buildMoaDetails($amt));
        }
        $lin->appendChild($linMoa);

        return $lin;
    }

    private function buildInvoiceMoa(array $amounts): DOMElement
    {
        $invoiceMoa = $this->dom->createElement('InvoiceMoa');
        foreach ($amounts as $amt) {
            $invoiceMoa->appendChild($this->buildMoaDetails($amt));
        }
        return $invoiceMoa;
    }

    private function buildMoaDetails(array $amt): DOMElement
    {
        $amtDetails = $this->dom->createElement('AmountDetails');
        $moa = $this->dom->createElement('Moa');
        $moa->setAttribute('amountTypeCode', $amt['amount_type_code']);
        $moa->setAttribute('currencyCodeList', $amt['currency_code_list'] ?? 'ISO_4217');

        $amount = $this->dom->createElement('Amount',
                    htmlspecialchars($amt['amount']));
        $amount->setAttribute('currencyIdentifier', $amt['currency_identifier'] ?? 'TND');
        $moa->appendChild($amount);

        if (!empty($amt['description'])) {
            $desc = $this->dom->createElement('AmountDescription',
                      htmlspecialchars($amt['description']));
            if (!empty($amt['description_lang'])) {
                $desc->setAttribute('lang', $amt['description_lang']);
            }
            $moa->appendChild($desc);
        }

        $amtDetails->appendChild($moa);
        return $amtDetails;
    }

    private function buildInvoiceTax(Invoice $invoice): DOMElement
    {
        $invoiceTax = $this->dom->createElement('InvoiceTax');
        foreach ($invoice->taxes as $tax) {
            $taxDetails = $this->dom->createElement('InvoiceTaxDetails');

            $taxEl = $this->dom->createElement('Tax');
            $taxName = $this->dom->createElement('TaxTypeName',
                         htmlspecialchars($tax->tax_type_name));
            $taxName->setAttribute('code', $tax->tax_type_code);
            $taxEl->appendChild($taxName);

            $td = $this->dom->createElement('TaxDetails');
            $this->appendTextChild($td, 'TaxRate', $tax->tax_rate);
            $taxEl->appendChild($td);
            $taxDetails->appendChild($taxEl);

            foreach ($tax->amounts as $amt) {
                $taxDetails->appendChild($this->buildMoaDetails($amt));
            }

            $invoiceTax->appendChild($taxDetails);
        }
        return $invoiceTax;
    }

    private function buildRefTtnVal(Invoice $invoice): DOMElement
    {
        $refTtn = $this->dom->createElement('RefTtnVal');

        $refTtnEl = $this->dom->createElement('ReferenceTTN',
                      htmlspecialchars($invoice->ref_ttn_value));
        $refTtnEl->setAttribute('refID', $invoice->ref_ttn_id ?? 'I-88');
        $refTtn->appendChild($refTtnEl);

        $this->appendTextChild($refTtn, 'ReferenceCEV', $invoice->ref_cev ?? '');

        if (!empty($invoice->ref_ttn_dates)) {
            $refDate = $this->dom->createElement('ReferenceDate');
            foreach ($invoice->ref_ttn_dates as $d) {
                $dateText = $this->dom->createElement('DateText',
                              htmlspecialchars($d['value']));
                $dateText->setAttribute('functionCode', $d['function_code']);
                $dateText->setAttribute('format', $d['format']);
                $refDate->appendChild($dateText);
            }
            $refTtn->appendChild($refDate);
        }

        return $refTtn;
    }

    private function buildSignatureStub(array $sig): DOMElement
    {
        $sigEl = $this->dom->createElementNS($this->dsNs, 'ds:Signature');
        $sigEl->setAttribute('Id', $sig['id'] ?? 'SigFrs');
        // Full XAdES signature building is handled by SigningService
        return $sigEl;
    }

    private function appendTextChild(DOMElement $parent, string $tag, string $value): void
    {
        $el = $this->dom->createElement($tag, htmlspecialchars($value));
        $parent->appendChild($el);
    }
}