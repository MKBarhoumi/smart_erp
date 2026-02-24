<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Exception;

class TeifXmlParser
{
    private DOMDocument $dom;
    private DOMXPath $xpath;

    /**
     * Parse a TEIF XML string and return structured array.
     */
    public function parse(string $xml): array
    {
        $this->dom = new DOMDocument();
        $this->dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING);

        $this->xpath = new DOMXPath($this->dom);
        $this->xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $this->xpath->registerNamespace('xades', 'http://uri.etsi.org/01903/v1.3.2#');

        $root = $this->dom->documentElement;

        return [
            'version'            => $root->getAttribute('version'),
            'controlling_agency' => $root->getAttribute('controlingAgency'),
            'header'             => $this->parseHeader(),
            'body'               => $this->parseBody(),
            'additional_docs'    => $this->parseAdditionalDocs(),
            'ref_ttn_val'        => $this->parseRefTtnVal(),
            'signatures'         => $this->parseSignatures(),
        ];
    }

    private function parseHeader(): array
    {
        $sender   = $this->xpath->query('//InvoiceHeader/MessageSenderIdentifier')->item(0);
        $receiver = $this->xpath->query('//InvoiceHeader/MessageRecieverIdentifier')->item(0);

        return [
            'sender_identifier' => $sender?->nodeValue ?? '',
            'sender_type'       => $sender?->getAttribute('type') ?? 'I-01',
            'receiver_identifier' => $receiver?->nodeValue ?? '',
            'receiver_type'       => $receiver?->getAttribute('type') ?? '',
        ];
    }

    private function parseBody(): array
    {
        return [
            'bgm'               => $this->parseBgm(),
            'dtm'               => $this->parseDtm('//InvoiceBody/Dtm/DateText'),
            'partners'          => $this->parsePartners(),
            'loc_section'       => $this->parseLocSection(),
            'payment_section'   => $this->parsePaymentSection(),
            'free_texts'        => $this->parseFtx('//InvoiceBody/Ftx'),
            'special_conditions'=> $this->parseSpecialConditions(),
            'lines'             => $this->parseLines(),
            'invoice_amounts'   => $this->parseInvoiceMoa(),
            'invoice_taxes'     => $this->parseInvoiceTax(),
            'invoice_allowances'=> $this->parseInvoiceAlc(),
        ];
    }

    private function parseBgm(): array
    {
        $docId   = $this->xpath->query('//InvoiceBody/Bgm/DocumentIdentifier')->item(0);
        $docType = $this->xpath->query('//InvoiceBody/Bgm/DocumentType')->item(0);
        $refs    = $this->xpath->query('//InvoiceBody/Bgm/DocumentReferences/DocumentReference');

        $references = [];
        foreach ($refs as $ref) {
            $references[] = $this->parseRefGrp($ref);
        }

        return [
            'document_identifier'  => $docId?->nodeValue ?? '',
            'document_type_code'   => $docType?->getAttribute('code') ?? '',
            'document_type_name'   => $docType?->nodeValue ?? '',
            'document_references'  => $references,
        ];
    }

    private function parseDtm(string $xpathQuery): array
    {
        $nodes = $this->xpath->query($xpathQuery);
        $dates = [];
        foreach ($nodes as $node) {
            $dates[] = [
                'function_code' => $node->getAttribute('functionCode'),
                'format'        => $node->getAttribute('format'),
                'value'         => $node->nodeValue,
            ];
        }
        return $dates;
    }

    private function parsePartners(): array
    {
        $partnerNodes = $this->xpath->query('//InvoiceBody/PartnerSection/PartnerDetails');
        $partners = [];

        foreach ($partnerNodes as $pNode) {
            $nad = $this->xpath->query('Nad', $pNode)->item(0);
            $partners[] = [
                'function_code'            => $pNode->getAttribute('functionCode'),
                'partner_identifier'       => $this->xval($nad, 'PartnerIdentifier'),
                'partner_identifier_type'  => $this->xattr($nad, 'PartnerIdentifier', 'type'),
                'partner_name'             => $this->xval($nad, 'PartnerName'),
                'partner_name_type'        => $this->xattr($nad, 'PartnerName', 'nameType'),
                'addresses'                => $this->parseAddresses($nad),
                'locations'                => $this->parseLocations($pNode),
                'references'               => $this->parseRffSections($pNode),
                'contacts'                 => $this->parseCtaSections($pNode),
            ];
        }
        return $partners;
    }

    private function parseAddresses(?object $parent): array
    {
        if (!$parent) return [];
        $addrs = $this->xpath->query('PartnerAdresses', $parent);
        $result = [];
        foreach ($addrs as $addr) {
            $country = $this->xpath->query('Country', $addr)->item(0);
            $result[] = [
                'description' => $this->xval($addr, 'AdressDescription'),
                'street'      => $this->xval($addr, 'Street'),
                'city'        => $this->xval($addr, 'CityName'),
                'postal_code' => $this->xval($addr, 'PostalCode'),
                'country'     => $country?->nodeValue ?? '',
                'country_code_list' => $country?->getAttribute('codeList') ?? '',
                'lang'        => $addr->getAttribute('lang'),
            ];
        }
        return $result;
    }

    private function parseLocations(object $parent): array
    {
        $nodes = $this->xpath->query('Loc', $parent);
        $result = [];
        foreach ($nodes as $n) {
            $result[] = [
                'function_code' => $n->getAttribute('functionCode'),
                'value'         => $n->nodeValue,
            ];
        }
        return $result;
    }

    private function parseRffSections(object $parent): array
    {
        $nodes = $this->xpath->query('RffSection', $parent);
        $result = [];
        foreach ($nodes as $n) {
            $result[] = $this->parseRefGrp($n);
        }
        return $result;
    }

    private function parseRefGrp(object $node): array
    {
        $ref  = $this->xpath->query('Reference', $node)->item(0);
        $dtm  = $this->xpath->query('ReferenceDate/DateText', $node);
        $dates = [];
        foreach ($dtm as $d) {
            $dates[] = [
                'function_code' => $d->getAttribute('functionCode'),
                'format'        => $d->getAttribute('format'),
                'value'         => $d->nodeValue,
            ];
        }
        return [
            'ref_id' => $ref?->getAttribute('refID') ?? '',
            'value'  => $ref?->nodeValue ?? '',
            'dates'  => $dates,
        ];
    }

    private function parseCtaSections(object $parent): array
    {
        $nodes = $this->xpath->query('CtaSection', $parent);
        $result = [];
        foreach ($nodes as $n) {
            $contact = $this->xpath->query('Contact', $n)->item(0);
            $comm    = $this->xpath->query('Communication', $n)->item(0);
            $result[] = [
                'function_code'       => $contact?->getAttribute('functionCode') ?? '',
                'contact_identifier'  => $this->xval($contact, 'ContactIdentifier'),
                'contact_name'        => $this->xval($contact, 'ContactName'),
                'com_means_type'      => $comm ? $this->xval($comm, 'ComMeansType') : null,
                'com_address'         => $comm ? $this->xval($comm, 'ComAdress') : null,
            ];
        }
        return $result;
    }

    private function parseLocSection(): array
    {
        $nodes = $this->xpath->query('//InvoiceBody/LocSection/LocDetails');
        $result = [];
        foreach ($nodes as $n) {
            $result[] = [
                'function_code' => $n->getAttribute('functionCode'),
                'value'         => $n->nodeValue,
            ];
        }
        return $result;
    }

    private function parsePaymentSection(): array
    {
        $nodes = $this->xpath->query('//InvoiceBody/PytSection/PytSectionDetails');
        $result = [];
        foreach ($nodes as $n) {
            $pyt = $this->xpath->query('Pyt', $n)->item(0);
            $fii = $this->xpath->query('PytFii', $n)->item(0);

            $entry = [
                'payment_terms_type_code' => $pyt ? $this->xval($pyt, 'PaymentTearmsTypeCode') : null,
                'payment_terms_description' => $pyt ? $this->xval($pyt, 'PaymentTearmsDescription') : null,
                'dtm'  => $this->parseDtm("//PytSection/PytSectionDetails/PytDtm/DateText"),
                'moa'  => null,
                'pai'  => null,
                'fii'  => null,
            ];

            if ($fii) {
                $acct = $this->xpath->query('AccountHolder', $fii)->item(0);
                $inst = $this->xpath->query('InstitutionIdentification', $fii)->item(0);
                $entry['fii'] = [
                    'function_code'     => $fii->getAttribute('functionCode'),
                    'account_number'    => $acct ? $this->xval($acct, 'AccountNumber') : null,
                    'owner_identifier'  => $acct ? $this->xval($acct, 'OwnerIdentifier') : null,
                    'name_code'         => $inst?->getAttribute('nameCode') ?? '',
                    'branch_identifier' => $inst ? $this->xval($inst, 'BranchIdentifier') : null,
                    'institution_name'  => $inst ? $this->xval($inst, 'InstitutionName') : null,
                ];
            }

            $result[] = $entry;
        }
        return $result;
    }

    private function parseFtx(string $xpathBase): array
    {
        $details = $this->xpath->query($xpathBase . '/FreeTextDetail');
        $result  = [];
        foreach ($details as $d) {
            $texts = $this->xpath->query('FreeTexts', $d);
            $textArr = [];
            foreach ($texts as $t) {
                $textArr[] = $t->nodeValue;
            }
            $result[] = [
                'subject_code' => $d->getAttribute('subjectCode'),
                'texts'        => $textArr,
            ];
        }
        return $result;
    }

    private function parseSpecialConditions(): array
    {
        $nodes = $this->xpath->query('//InvoiceBody/SpecialConditions/SpecialCondition');
        $result = [];
        foreach ($nodes as $n) {
            $result[] = $n->nodeValue;
        }
        return $result;
    }

    private function parseLines(): array
    {
        $linNodes = $this->xpath->query('//InvoiceBody/LinSection/Lin');
        $lines = [];
        foreach ($linNodes as $lin) {
            $lines[] = $this->parseOneLin($lin);
        }
        return $lines;
    }

    private function parseOneLin(object $lin): array
    {
        $imd = $this->xpath->query('LinImd', $lin)->item(0);
        $tax = $this->xpath->query('LinTax', $lin)->item(0);
        $qty = $this->xpath->query('LinQty/Quantity', $lin)->item(0);

        // Sub-lines
        $subLinNodes = $this->xpath->query('SubLin', $lin);
        $subLines = [];
        foreach ($subLinNodes as $sub) {
            $subLines[] = $this->parseOneLin($sub);
        }

        return [
            'item_identifier'  => $this->xval($lin, 'ItemIdentifier'),
            'item_code'        => $imd ? $this->xval($imd, 'ItemCode') : '',
            'item_description' => $imd ? $this->xval($imd, 'ItemDescription') : '',
            'item_lang'        => $imd?->getAttribute('lang') ?? '',
            'api_details'      => $this->parseApiDetails($lin),
            'quantity'         => $qty?->nodeValue ?? '',
            'measurement_unit' => $qty?->getAttribute('measurementUnit') ?? '',
            'dates'            => $this->parseDtm("LinDtm/DateText"),
            'tax_type_code'    => $tax ? $this->xattr($tax, 'TaxTypeName', 'code') : '',
            'tax_type_name'    => $tax ? $this->xval($tax, 'TaxTypeName') : '',
            'tax_category'     => $tax ? $this->xval($tax, 'TaxCategory') : '',
            'tax_rate'         => $tax ? $this->xpath->query('TaxDetails/TaxRate', $tax)->item(0)?->nodeValue : '',
            'tax_rate_basis'   => $tax ? $this->xpath->query('TaxDetails/TaxRateBasis', $tax)->item(0)?->nodeValue : '',
            'allowances'       => $this->parseLinAlc($lin),
            'amounts'          => $this->parseMoaDetails($lin, 'LinMoa/MoaDetails'),
            'free_texts'       => $this->parseFtx("LinFtx"),
            'sub_lines'        => $subLines,
        ];
    }

    private function parseApiDetails(object $parent): array
    {
        $nodes = $this->xpath->query('LinApi/ApiDetails', $parent);
        $result = [];
        foreach ($nodes as $n) {
            $result[] = [
                'lang'        => $n->getAttribute('lang'),
                'code'        => $this->xval($n, 'ApiCode'),
                'description' => $this->xval($n, 'ApiDescription'),
            ];
        }
        return $result;
    }

    private function parseLinAlc(object $parent): array
    {
        $alc = $this->xpath->query('LinAlc', $parent)->item(0);
        if (!$alc) return [];

        $alcNode = $this->xpath->query('Alc', $alc)->item(0);
        $pcd     = $this->xpath->query('Pcd', $alc)->item(0);

        return [[
            'allowance_code'       => $alcNode?->getAttribute('allowanceCode') ?? '',
            'allowance_identifier' => $alcNode ? $this->xval($alcNode, 'AllowanceIdentifier') : '',
            'special_services'     => $alcNode ? $this->xval($alcNode, 'SpecialServices') : '',
            'percentage'           => $pcd ? $this->xval($pcd, 'Percentage') : '',
            'percentage_basis'     => $pcd ? $this->xval($pcd, 'PercentageBasis') : '',
        ]];
    }

    private function parseMoaDetails(object $parent, string $xpathExpr): array
    {
        $nodes = $this->xpath->query($xpathExpr, $parent);
        $result = [];
        foreach ($nodes as $n) {
            $moa    = $this->xpath->query('Moa', $n)->item(0);
            $amount = $moa ? $this->xpath->query('Amount', $moa)->item(0) : null;
            $desc   = $moa ? $this->xpath->query('AmountDescription', $moa)->item(0) : null;

            $result[] = [
                'amount_type_code'    => $moa?->getAttribute('amountTypeCode') ?? '',
                'currency_code_list'  => $moa?->getAttribute('currencyCodeList') ?? '',
                'currency_identifier' => $amount?->getAttribute('currencyIdentifier') ?? '',
                'amount'              => $amount?->nodeValue ?? '',
                'description'         => $desc?->nodeValue ?? '',
                'description_lang'    => $desc?->getAttribute('lang') ?? '',
            ];
        }
        return $result;
    }

    private function parseInvoiceMoa(): array
    {
        $root = $this->dom->documentElement;
        $node = $this->xpath->query('//InvoiceBody/InvoiceMoa')->item(0);
        if (!$node) return [];
        return $this->parseMoaDetails($node, 'AmountDetails');
    }

    private function parseInvoiceTax(): array
    {
        $nodes = $this->xpath->query('//InvoiceBody/InvoiceTax/InvoiceTaxDetails');
        $result = [];
        foreach ($nodes as $n) {
            $tax = $this->xpath->query('Tax', $n)->item(0);
            $result[] = [
                'tax_type_code'  => $tax ? $this->xattr($tax, 'TaxTypeName', 'code') : '',
                'tax_type_name'  => $tax ? $this->xval($tax, 'TaxTypeName') : '',
                'tax_category'   => $tax ? $this->xval($tax, 'TaxCategory') : '',
                'tax_rate'       => $tax ? $this->xpath->query('TaxDetails/TaxRate', $tax)->item(0)?->nodeValue : '',
                'amounts'        => $this->parseMoaDetails($n, 'AmountDetails'),
            ];
        }
        return $result;
    }

    private function parseInvoiceAlc(): array
    {
        $nodes = $this->xpath->query('//InvoiceBody/InvoiceAlc/AllowanceDetails');
        $result = [];
        foreach ($nodes as $n) {
            $alc = $this->xpath->query('Alc', $n)->item(0);
            $moa = $this->xpath->query('Moa', $n)->item(0);
            $result[] = [
                'allowance_code'       => $alc?->getAttribute('allowanceCode') ?? '',
                'allowance_identifier' => $alc ? $this->xval($alc, 'AllowanceIdentifier') : '',
                'moa_amount_type_code' => $moa?->getAttribute('amountTypeCode') ?? '',
                'moa_amount'           => $moa ? $this->xpath->query('Amount', $moa)->item(0)?->nodeValue : '',
            ];
        }
        return $result;
    }

    private function parseRefTtnVal(): ?array
    {
        $node = $this->xpath->query('//RefTtnVal')->item(0);
        if (!$node) return null;

        $refTtn = $this->xpath->query('ReferenceTTN', $node)->item(0);
        return [
            'ref_id'    => $refTtn?->getAttribute('refID') ?? '',
            'value'     => $refTtn?->nodeValue ?? '',
            'ref_cev'   => $this->xval($node, 'ReferenceCEV'),
            'dates'     => $this->parseDtm("//RefTtnVal/ReferenceDate/DateText"),
        ];
    }

    private function parseSignatures(): array
    {
        $nodes = $this->xpath->query('//ds:Signature');
        $result = [];
        foreach ($nodes as $n) {
            $sigId   = $n->getAttribute('Id');
            $sigVal  = $this->xpath->query('ds:SignatureValue', $n)->item(0);
            $sigTime = $this->xpath->query('.//xades:SigningTime', $n)->item(0);
            $role    = $this->xpath->query('.//xades:ClaimedRole', $n)->item(0);
            $cert    = $this->xpath->query('ds:KeyInfo/ds:X509Data/ds:X509Certificate', $n)->item(0);

            $result[] = [
                'id'           => $sigId,
                'value'        => $sigVal?->nodeValue ?? '',
                'signing_time' => $sigTime?->nodeValue ?? '',
                'role'         => $role?->nodeValue ?? '',
                'certificate'  => $cert?->nodeValue ?? '',
            ];
        }
        return $result;
    }

    private function parseAdditionalDocs(): ?array
    {
        $node = $this->xpath->query('//AdditionnalDocuments')->item(0);
        if (!$node) return null;
        return [
            'identifier' => $this->xval($node, 'AdditionnalDocumentIdentifier'),
            'name'       => $this->xval($node, 'AdditionnalDocumentName'),
            'dates'      => $this->parseDtm("//AdditionnalDocuments/AdditionnalDocumentDate/DateText"),
        ];
    }

    // ---- Helpers ----

    private function xval(?object $parent, string $childTag): string
    {
        if (!$parent) return '';
        $node = $this->xpath->query($childTag, $parent)->item(0);
        return $node?->nodeValue ?? '';
    }

    private function xattr(?object $parent, string $childTag, string $attr): string
    {
        if (!$parent) return '';
        $node = $this->xpath->query($childTag, $parent)->item(0);
        return $node?->getAttribute($attr) ?? '';
    }
}