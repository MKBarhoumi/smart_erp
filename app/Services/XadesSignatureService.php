<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SignatureException;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * XAdES-BES digital signature service for TEIF XML.
 *
 * Implements the 11-step workflow:
 * 1. Load unsigned TEIF XML
 * 2. Canonicalize <OldInvoiceBody> (Exclusive C14N)
 * 3. Compute SHA-256 digest of canonicalized OldInvoiceBody
 * 4. Build <SignedInfo> with two <Reference> elements
 * 5. Compute SHA-256 digest of signing certificate
 * 6. Build <SignedProperties> (xades:SigningTime, xades:SigningCertificate)
 * 7. Canonicalize <SignedProperties> and compute digest
 * 8. Insert reference to SignedProperties in <SignedInfo>
 * 9. Canonicalize <SignedInfo> and sign with RSA-SHA256
 * 10. Assemble <ds:Signature> block
 * 11. Inject signature into TEIF XML
 */
class XadesSignatureService
{
    private const DS_NS = 'http://www.w3.org/2000/09/xmldsig#';
    private const XADES_NS = 'http://uri.etsi.org/01903/v1.3.2#';
    private const C14N_EXC = 'http://www.w3.org/2001/10/xml-exc-c14n#';
    private const SIGN_ALG = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';
    private const DIGEST_ALG = 'http://www.w3.org/2001/04/xmlenc#sha256';

    public function __construct(
        private readonly CertificateManager $certificateManager,
    ) {
    }

    /**
     * Sign a TEIF XML string, returning the signed XML.
     *
     * @throws SignatureException
     */
    public function sign(string $unsignedXml): string
    {
        // Step 1: Load unsigned XML
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $prevErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($unsignedXml);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);

        if (!$loaded) {
            throw new SignatureException('Failed to load TEIF XML for signing.');
        }

        // Step 2: Canonicalize OldInvoiceBody
        $xpath = new DOMXPath($dom);
        $oldinvoiceBodyNodes = $xpath->query('//OldInvoiceBody');
        if ($oldinvoiceBodyNodes === false || $oldinvoiceBodyNodes->length === 0) {
            throw new SignatureException('OldInvoiceBody element not found in TEIF XML.');
        }

        /** @var DOMElement $oldinvoiceBody */
        $oldinvoiceBody = $oldinvoiceBodyNodes->item(0);
        $canonicalBody = $oldinvoiceBody->C14N(true, false);

        // Step 3: SHA-256 digest of OldInvoiceBody
        $bodyDigest = base64_encode(hash('sha256', $canonicalBody, true));

        // Step 4-5: Get certificate info
        $certPem = $this->certificateManager->getSigningCertificate();
        $certDer = $this->certificateManager->getSigningCertificateDer();
        $certDigest = base64_encode(hash('sha256', $certDer, true));
        $certSerial = $this->certificateManager->getSerialNumber();
        $certIssuer = $this->certificateManager->getIssuerDN();

        // Step 6: Build SignedProperties
        $signatureId = 'xmldsig-' . bin2hex(random_bytes(8));
        $signingTime = gmdate('Y-m-d\TH:i:s\Z');

        $signedPropsXml = $this->buildSignedProperties(
            $signatureId,
            $signingTime,
            $certDigest,
            $certSerial,
            $certIssuer,
        );

        // Step 7: Canonicalize SignedProperties and compute digest
        $propsDom = new DOMDocument();
        $propsDom->loadXML($signedPropsXml);
        $propsCanonical = $propsDom->documentElement->C14N(true, false);
        $propsDigest = base64_encode(hash('sha256', $propsCanonical, true));

        // Step 8-9: Build SignedInfo and sign
        $signedInfoXml = $this->buildSignedInfo(
            $signatureId,
            $bodyDigest,
            $propsDigest,
        );

        $signedInfoDom = new DOMDocument();
        $signedInfoDom->loadXML($signedInfoXml);
        $signedInfoCanonical = $signedInfoDom->documentElement->C14N(true, false);

        // RSA-SHA256 signature
        $privateKeyPem = $this->certificateManager->getPrivateKey();
        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if ($privateKey === false) {
            throw new SignatureException('Failed to load private key: ' . openssl_error_string());
        }

        $signatureValue = '';
        $signResult = openssl_sign($signedInfoCanonical, $signatureValue, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$signResult) {
            throw new SignatureException('Failed to compute RSA-SHA256 signature: ' . openssl_error_string());
        }

        $signatureValueB64 = base64_encode($signatureValue);

        // Step 10: Assemble full ds:Signature
        $certB64 = $this->getCertificateBase64($certPem);
        $chainCerts = $this->certificateManager->getCertificateChain();

        $signatureBlock = $this->assembleSignature(
            $signatureId,
            $signedInfoXml,
            $signatureValueB64,
            $certB64,
            $chainCerts,
            $signedPropsXml,
        );

        // Step 11: Inject signature into TEIF
        $signatureDom = new DOMDocument();
        $signatureDom->loadXML($signatureBlock);
        $importedNode = $dom->importNode($signatureDom->documentElement, true);
        $dom->documentElement->appendChild($importedNode);

        $signedXml = $dom->saveXML();
        if ($signedXml === false) {
            throw new SignatureException('Failed to output signed XML.');
        }

        return $signedXml;
    }

    /**
     * Verify the signature of a signed TEIF XML.
     *
     * @throws SignatureException
     */
    public function verify(string $signedXml): bool
    {
        $dom = new DOMDocument();

        $prevErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($signedXml);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);

        if (!$loaded) {
            throw new SignatureException('Failed to load signed XML for verification.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ds', self::DS_NS);

        // Extract SignatureValue
        $sigValueNodes = $xpath->query('//ds:Signature/ds:SignatureValue');
        if ($sigValueNodes === false || $sigValueNodes->length === 0) {
            throw new SignatureException('SignatureValue not found.');
        }
        $signatureValue = base64_decode(trim($sigValueNodes->item(0)->textContent), true);

        // Extract and canonicalize SignedInfo
        $signedInfoNodes = $xpath->query('//ds:Signature/ds:SignedInfo');
        if ($signedInfoNodes === false || $signedInfoNodes->length === 0) {
            throw new SignatureException('SignedInfo not found.');
        }
        /** @var DOMElement $signedInfoElem */
        $signedInfoElem = $signedInfoNodes->item(0);
        $signedInfoCanonical = $signedInfoElem->C14N(true, false);

        // Extract certificate
        $certNodes = $xpath->query('//ds:Signature/ds:KeyInfo/ds:X509Data/ds:X509Certificate');
        if ($certNodes === false || $certNodes->length === 0) {
            throw new SignatureException('X509Certificate not found.');
        }
        $certB64 = trim($certNodes->item(0)->textContent);
        $certPem = "-----BEGIN CERTIFICATE-----\n" . wordwrap($certB64, 64, "\n", true) . "\n-----END CERTIFICATE-----";

        $pubKey = openssl_pkey_get_public($certPem);
        if ($pubKey === false) {
            throw new SignatureException('Failed to extract public key from certificate.');
        }

        $result = openssl_verify($signedInfoCanonical, $signatureValue, $pubKey, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }

    private function buildSignedProperties(
        string $signatureId,
        string $signingTime,
        string $certDigest,
        string $certSerial,
        string $certIssuer,
    ): string {
        return <<<XML
<xades:SignedProperties xmlns:xades="{$this->escape(self::XADES_NS)}" xmlns:ds="{$this->escape(self::DS_NS)}" Id="{$this->escape($signatureId)}-signedprops">
  <xades:SignedSignatureProperties>
    <xades:SigningTime>{$this->escape($signingTime)}</xades:SigningTime>
    <xades:SigningCertificate>
      <xades:Cert>
        <xades:CertDigest>
          <ds:DigestMethod Algorithm="{$this->escape(self::DIGEST_ALG)}"/>
          <ds:DigestValue>{$this->escape($certDigest)}</ds:DigestValue>
        </xades:CertDigest>
        <xades:IssuerSerial>
          <ds:X509IssuerName>{$this->escape($certIssuer)}</ds:X509IssuerName>
          <ds:X509SerialNumber>{$this->escape($certSerial)}</ds:X509SerialNumber>
        </xades:IssuerSerial>
      </xades:Cert>
    </xades:SigningCertificate>
  </xades:SignedSignatureProperties>
</xades:SignedProperties>
XML;
    }

    private function buildSignedInfo(
        string $signatureId,
        string $bodyDigest,
        string $propsDigest,
    ): string {
        return <<<XML
<ds:SignedInfo xmlns:ds="{$this->escape(self::DS_NS)}">
  <ds:CanonicalizationMethod Algorithm="{$this->escape(self::C14N_EXC)}"/>
  <ds:SignatureMethod Algorithm="{$this->escape(self::SIGN_ALG)}"/>
  <ds:Reference URI="">
    <ds:Transforms>
      <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
      <ds:Transform Algorithm="{$this->escape(self::C14N_EXC)}"/>
    </ds:Transforms>
    <ds:DigestMethod Algorithm="{$this->escape(self::DIGEST_ALG)}"/>
    <ds:DigestValue>{$this->escape($bodyDigest)}</ds:DigestValue>
  </ds:Reference>
  <ds:Reference URI="#{$this->escape($signatureId)}-signedprops" Type="http://uri.etsi.org/01903#SignedProperties">
    <ds:DigestMethod Algorithm="{$this->escape(self::DIGEST_ALG)}"/>
    <ds:DigestValue>{$this->escape($propsDigest)}</ds:DigestValue>
  </ds:Reference>
</ds:SignedInfo>
XML;
    }

    /**
     * @param string[] $chainCerts
     */
    private function assembleSignature(
        string $signatureId,
        string $signedInfoXml,
        string $signatureValueB64,
        string $certB64,
        array $chainCerts,
        string $signedPropsXml,
    ): string {
        $chainXml = '';
        foreach ($chainCerts as $chainCert) {
            $b64 = $this->getCertificateBase64($chainCert);
            $chainXml .= "\n      <ds:X509Certificate>{$b64}</ds:X509Certificate>";
        }

        return <<<XML
<ds:Signature xmlns:ds="{$this->escape(self::DS_NS)}" Id="{$this->escape($signatureId)}">
  {$signedInfoXml}
  <ds:SignatureValue Id="{$this->escape($signatureId)}-sigvalue">{$this->escape($signatureValueB64)}</ds:SignatureValue>
  <ds:KeyInfo>
    <ds:X509Data>
      <ds:X509Certificate>{$this->escape($certB64)}</ds:X509Certificate>{$chainXml}
    </ds:X509Data>
  </ds:KeyInfo>
  <ds:Object>
    <xades:QualifyingProperties xmlns:xades="{$this->escape(self::XADES_NS)}" Target="#{$this->escape($signatureId)}">
      {$signedPropsXml}
    </xades:QualifyingProperties>
  </ds:Object>
</ds:Signature>
XML;
    }

    private function getCertificateBase64(string $pem): string
    {
        $cleaned = preg_replace('/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s/', '', $pem);
        return $cleaned ?? '';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
