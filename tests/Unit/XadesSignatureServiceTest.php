<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\SignatureException;
use App\Services\CertificateManager;
use App\Services\XadesSignatureService;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class XadesSignatureServiceTest extends TestCase
{
    private XadesSignatureService $service;
    /** @var CertificateManager&MockObject */
    private CertificateManager $certManager;

    /** @var string Test RSA private key PEM */
    private string $privateKeyPem = '';
    /** @var string Test certificate PEM */
    private string $certPem = '';
    /** @var string Test certificate DER (raw) */
    private string $certDer = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Generate a self-signed test certificate
        // On Windows, openssl_pkey_new() needs an explicit config path
        $keyConfig = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Try to locate openssl.cnf for Windows environments
        $opensslCnf = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'extras' . DIRECTORY_SEPARATOR . 'ssl' . DIRECTORY_SEPARATOR . 'openssl.cnf';
        if (file_exists($opensslCnf)) {
            $keyConfig['config'] = $opensslCnf;
        }

        $privateKey = openssl_pkey_new($keyConfig);
        if ($privateKey === false) {
            $this->markTestSkipped('Cannot generate RSA key — openssl.cnf not found or OpenSSL misconfigured');
        }

        openssl_pkey_export($privateKey, $this->privateKeyPem, null, $keyConfig);

        $csr = openssl_csr_new([
            'CN' => 'Test Signer',
            'O' => 'Test Org',
            'C' => 'TN',
        ], $privateKey, $keyConfig);

        $cert = openssl_csr_sign($csr, null, $privateKey, 365, $keyConfig);
        openssl_x509_export($cert, $this->certPem);

        // DER encoding
        $cleaned = preg_replace('/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s/', '', $this->certPem);
        $this->certDer = base64_decode($cleaned, true);

        // Get cert info
        $certInfo = openssl_x509_parse($this->certPem);

        // Mock CertificateManager
        $this->certManager = $this->createMock(CertificateManager::class);
        $this->certManager->method('getSigningCertificate')->willReturn($this->certPem);
        $this->certManager->method('getSigningCertificateDer')->willReturn($this->certDer);
        $this->certManager->method('getPrivateKey')->willReturn($this->privateKeyPem);
        $this->certManager->method('getSerialNumber')->willReturn($certInfo['serialNumberHex'] ?? 'ABCDEF01');
        $this->certManager->method('getIssuerDN')->willReturn('CN=Test Signer, O=Test Org, C=TN');
        $this->certManager->method('getCertificateChain')->willReturn([]);

        $this->service = new XadesSignatureService($this->certManager);
    }

    private function getSampleTeifXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<TEIF controlingAgency="TTN" version="1.8.8">
  <OldInvoiceHeader>
    <MessageSenderIdentifier type="I-01">0736202XAM000</MessageSenderIdentifier>
    <MessageRecieverIdentifier type="I-01">0914089JAM000</MessageRecieverIdentifier>
  </OldInvoiceHeader>
  <OldInvoiceBody>
    <Bgm>
      <DocumentIdentifier>FA-2026-0001</DocumentIdentifier>
      <DocumentType code="I-11">Facture</DocumentType>
    </Bgm>
    <Dtm>
      <DateText format="ddMMyy" functionCode="I-31">130226</DateText>
    </Dtm>
    <OldInvoiceMoa>
      <AmountDetails>
        <Moa amountTypeCode="I-180" currencyCodeList="ISO_4217">
          <Amount currencyIdentifier="TND">120.000</Amount>
        </Moa>
      </AmountDetails>
    </OldInvoiceMoa>
  </OldInvoiceBody>
</TEIF>
XML;
    }

    public function test_sign_returns_signed_xml_string(): void
    {
        $signedXml = $this->service->sign($this->getSampleTeifXml());

        $this->assertNotEmpty($signedXml);
        $this->assertStringContainsString('ds:Signature', $signedXml);
    }

    public function test_signed_xml_is_well_formed(): void
    {
        $signedXml = $this->service->sign($this->getSampleTeifXml());

        $dom = new DOMDocument();
        $loaded = $dom->loadXML($signedXml);
        $this->assertTrue($loaded, 'Signed XML should be well-formed');
    }

    public function test_signature_contains_signed_info(): void
    {
        $signedXml = $this->service->sign($this->getSampleTeifXml());
        $dom = new DOMDocument();
        $dom->loadXML($signedXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $signedInfo = $xpath->query('//ds:Signature/ds:SignedInfo');
        $this->assertEquals(1, $signedInfo->length, 'Should have exactly one SignedInfo');
    }

    public function test_signature_contains_signature_value(): void
    {
        $signedXml = $this->service->sign($this->getSampleTeifXml());
        $dom = new DOMDocument();
        $dom->loadXML($signedXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $sigValue = $xpath->query('//ds:Signature/ds:SignatureValue');
        $this->assertEquals(1, $sigValue->length);
        $this->assertNotEmpty(trim($sigValue->item(0)->textContent));
    }

    public function test_signature_contains_x509_certificate(): void
    {
        $signedXml = $this->service->sign($this->getSampleTeifXml());
        $dom = new DOMDocument();
        $dom->loadXML($signedXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $certs = $xpath->query('//ds:Signature/ds:KeyInfo/ds:X509Data/ds:X509Certificate');
        $this->assertGreaterThanOrEqual(1, $certs->length);
        $this->assertNotEmpty(trim($certs->item(0)->textContent));
    }

    public function test_signature_uses_correct_algorithms(): void
    {
        $signedXml = $this->service->sign($this->getSampleTeifXml());
        $dom = new DOMDocument();
        $dom->loadXML($signedXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        // CanonicalizationMethod
        $c14n = $xpath->query('//ds:SignedInfo/ds:CanonicalizationMethod');
        $this->assertEquals('http://www.w3.org/2001/10/xml-exc-c14n#', $c14n->item(0)->getAttribute('Algorithm'));

        // SignatureMethod
        $sigMethod = $xpath->query('//ds:SignedInfo/ds:SignatureMethod');
        $this->assertEquals('http://www.w3.org/2001/04/xmldsig-more#rsa-sha256', $sigMethod->item(0)->getAttribute('Algorithm'));

        // DigestMethod
        $digestMethods = $xpath->query('//ds:SignedInfo/ds:Reference/ds:DigestMethod');
        for ($i = 0; $i < $digestMethods->length; $i++) {
            $this->assertEquals('http://www.w3.org/2001/04/xmlenc#sha256', $digestMethods->item($i)->getAttribute('Algorithm'));
        }
    }

    public function test_signature_has_two_references(): void
    {
        $signedXml = $this->service->sign($this->getSampleTeifXml());
        $dom = new DOMDocument();
        $dom->loadXML($signedXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $references = $xpath->query('//ds:SignedInfo/ds:Reference');
        $this->assertEquals(2, $references->length, 'Should have 2 references: body + signed properties');
    }

    public function test_signature_contains_xades_signed_properties(): void
    {
        $signedXml = $this->service->sign($this->getSampleTeifXml());
        $dom = new DOMDocument();
        $dom->loadXML($signedXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('xades', 'http://uri.etsi.org/01903/v1.3.2#');

        $signedProps = $xpath->query('//xades:SignedProperties');
        $this->assertEquals(1, $signedProps->length);
    }

    public function test_xades_has_signing_time(): void
    {
        $signedXml = $this->service->sign($this->getSampleTeifXml());
        $dom = new DOMDocument();
        $dom->loadXML($signedXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('xades', 'http://uri.etsi.org/01903/v1.3.2#');

        $signingTime = $xpath->query('//xades:SigningTime');
        $this->assertEquals(1, $signingTime->length);
        // ISO 8601 UTC format
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $signingTime->item(0)->textContent);
    }

    public function test_xades_has_signing_certificate(): void
    {
        $signedXml = $this->service->sign($this->getSampleTeifXml());
        $dom = new DOMDocument();
        $dom->loadXML($signedXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('xades', 'http://uri.etsi.org/01903/v1.3.2#');
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $certDigest = $xpath->query('//xades:SigningCertificate/xades:Cert/xades:CertDigest/ds:DigestValue');
        $this->assertEquals(1, $certDigest->length);
        $this->assertNotEmpty(trim($certDigest->item(0)->textContent));

        $issuerName = $xpath->query('//xades:SigningCertificate/xades:Cert/xades:IssuerSerial/ds:X509IssuerName');
        $this->assertEquals(1, $issuerName->length);

        $serialNumber = $xpath->query('//xades:SigningCertificate/xades:Cert/xades:IssuerSerial/ds:X509SerialNumber');
        $this->assertEquals(1, $serialNumber->length);
    }

    public function test_signed_xml_preserves_original_content(): void
    {
        $original = $this->getSampleTeifXml();
        $signedXml = $this->service->sign($original);

        // The original content should still be present
        $this->assertStringContainsString('OldInvoiceHeader', $signedXml);
        $this->assertStringContainsString('OldInvoiceBody', $signedXml);
        $this->assertStringContainsString('FA-2026-0001', $signedXml);
        $this->assertStringContainsString('0736202XAM000', $signedXml);
    }

    public function test_verify_returns_true_for_valid_signature(): void
    {
        $signedXml = $this->service->sign($this->getSampleTeifXml());
        $isValid = $this->service->verify($signedXml);

        $this->assertTrue($isValid, 'A freshly signed XML should verify successfully');
    }

    public function test_sign_throws_on_invalid_xml(): void
    {
        $this->expectException(SignatureException::class);
        $this->service->sign('This is not valid XML');
    }

    public function test_sign_throws_on_missing_oldinvoice_body(): void
    {
        $this->expectException(SignatureException::class);
        $this->service->sign('<?xml version="1.0"?><TEIF><OldInvoiceHeader></OldInvoiceHeader></TEIF>');
    }

    public function test_verify_throws_on_invalid_xml(): void
    {
        $this->expectException(SignatureException::class);
        $this->service->verify('Not valid XML');
    }
}
