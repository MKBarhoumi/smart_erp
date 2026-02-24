<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;

/**
 * TEIF XML Validator.
 * 
 * Note: The official TEIF XSD uses XSD 1.1 features (xs:assert, xs:alternative) 
 * which PHP's libxml does not support. This validator performs structural 
 * validation instead of strict XSD validation.
 */
class TeifXsdValidator
{
    private array $requiredElements = [
        '/TEIF' => 'Root TEIF element',
        '/TEIF/InvoiceHeader' => 'InvoiceHeader section',
        '/TEIF/InvoiceHeader/MessageSenderIdentifier' => 'Sender identifier',
        '/TEIF/InvoiceHeader/MessageRecieverIdentifier' => 'Receiver identifier',
        '/TEIF/InvoiceBody' => 'InvoiceBody section',
        '/TEIF/InvoiceBody/Bgm/DocumentIdentifier' => 'Document identifier',
        '/TEIF/InvoiceBody/Bgm/DocumentType' => 'Document type',
        '/TEIF/InvoiceBody/Dtm/DateText' => 'At least one date',
        '/TEIF/InvoiceBody/PartnerSection/PartnerDetails' => 'At least one partner',
        '/TEIF/InvoiceBody/LinSection/Lin' => 'At least one line item',
        '/TEIF/InvoiceBody/InvoiceMoa/AmountDetails' => 'Invoice amounts',
        '/TEIF/InvoiceBody/InvoiceTax/InvoiceTaxDetails' => 'Tax details',
    ];

    private array $validDocumentTypes = ['I-11', 'I-12', 'I-13', 'I-14', 'I-15', 'I-16'];
    private array $validIdentifierTypes = ['I-01', 'I-02', 'I-03', 'I-04'];
    private array $validVersions = ['1.8.1', '1.8.2', '1.8.3', '1.8.4', '1.8.5', '1.8.6', '1.8.7', '1.8.8'];

    public function validate(string $xml, bool $withSignature = false): array
    {
        $errors = [];

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        if (!$dom->loadXML($xml)) {
            $errors = $this->collectLibxmlErrors();
            return ['valid' => false, 'errors' => $errors ?: ['Invalid XML structure']];
        }

        libxml_clear_errors();

        // Structural validation
        $xpath = new DOMXPath($dom);
        $root = $dom->documentElement;

        // Check root element
        if ($root->nodeName !== 'TEIF') {
            $errors[] = 'Root element must be TEIF';
        }

        // Check version attribute
        $version = $root->getAttribute('version');
        if (!in_array($version, $this->validVersions)) {
            $errors[] = "Invalid version: {$version}. Must be one of: " . implode(', ', $this->validVersions);
        }

        // Check controllingAgency
        $agency = $root->getAttribute('controlingAgency');
        if (!in_array($agency, ['TTN', 'Tunisie TradeNet'])) {
            $errors[] = "Invalid controlingAgency: {$agency}. Must be TTN or Tunisie TradeNet";
        }

        // Check required elements
        foreach ($this->requiredElements as $xpathExpr => $description) {
            $nodes = $xpath->query($xpathExpr);
            if ($nodes->length === 0) {
                $errors[] = "Missing required element: {$description} ({$xpathExpr})";
            }
        }

        // Validate document type code
        $docTypeNode = $xpath->query('/TEIF/InvoiceBody/Bgm/DocumentType')->item(0);
        if ($docTypeNode) {
            $code = $docTypeNode->getAttribute('code');
            if ($code && !in_array($code, $this->validDocumentTypes)) {
                $errors[] = "Invalid document type code: {$code}";
            }
        }

        // Validate sender identifier type
        $senderNode = $xpath->query('/TEIF/InvoiceHeader/MessageSenderIdentifier')->item(0);
        if ($senderNode) {
            $type = $senderNode->getAttribute('type');
            if ($type && !in_array($type, $this->validIdentifierTypes)) {
                $errors[] = "Invalid sender identifier type: {$type}";
            }
        }

        // Validate signature presence if required
        if ($withSignature) {
            $sigNodes = $xpath->query('//ds:Signature', $root);
            // Also try without namespace
            if ($sigNodes->length === 0) {
                $sigNodes = $xpath->query('//*[local-name()="Signature"]');
            }
            if ($sigNodes->length === 0) {
                $errors[] = 'Digital signature required but not found';
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    private function collectLibxmlErrors(): array
    {
        $errors = [];
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf(
                '[%s] Line %d: %s',
                $error->level === LIBXML_ERR_WARNING ? 'WARNING' : 'ERROR',
                $error->line,
                trim($error->message)
            );
        }
        libxml_clear_errors();
        return $errors;
    }
}