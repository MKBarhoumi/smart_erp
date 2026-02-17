<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SignatureException;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Storage;

/**
 * Manages .p12 certificate files: upload, parse, extract, validate expiry.
 */
class CertificateManager
{
    /**
     * Upload and parse a .p12 certificate file.
     *
     * @return array{subject: string, issuer: string, valid_from: string, valid_to: string, serial: string}
     *
     * @throws SignatureException
     */
    public function uploadAndParse(string $p12Contents, string $passphrase): array
    {
        $certs = [];
        $result = openssl_pkcs12_read($p12Contents, $certs, $passphrase);

        if (!$result || empty($certs['cert']) || empty($certs['pkey'])) {
            throw new SignatureException('Failed to read .p12 certificate. Check the passphrase.');
        }

        $certResource = openssl_x509_parse($certs['cert']);
        if ($certResource === false) {
            throw new SignatureException('Failed to parse X.509 certificate.');
        }

        // Store certificate and key
        $settings = CompanySetting::firstOrFail();
        $certDir = 'certificates';

        Storage::disk('local')->put("{$certDir}/certificate.pem", $certs['cert']);
        Storage::disk('local')->put("{$certDir}/private_key.pem", $certs['pkey']);

        if (!empty($certs['extracerts'])) {
            $chainPem = implode("\n", $certs['extracerts']);
            Storage::disk('local')->put("{$certDir}/chain.pem", $chainPem);
        }

        // Update company settings
        $settings->update([
            'certificate_file' => "{$certDir}/certificate.pem",
            'certificate_passphrase' => $passphrase,
            'certificate_expires_at' => date('Y-m-d H:i:s', $certResource['validTo_time_t']),
        ]);

        return [
            'subject' => $certResource['subject']['CN'] ?? $certResource['subject']['O'] ?? 'Unknown',
            'issuer' => $certResource['issuer']['CN'] ?? $certResource['issuer']['O'] ?? 'Unknown',
            'valid_from' => date('Y-m-d H:i:s', $certResource['validFrom_time_t']),
            'valid_to' => date('Y-m-d H:i:s', $certResource['validTo_time_t']),
            'serial' => $certResource['serialNumberHex'] ?? '',
        ];
    }

    /**
     * Get the signing certificate PEM.
     *
     * @throws SignatureException
     */
    public function getSigningCertificate(): string
    {
        $settings = CompanySetting::firstOrFail();

        if (empty($settings->certificate_file)) {
            throw new SignatureException('No signing certificate configured.');
        }

        $certPath = $settings->certificate_file;
        if (!Storage::disk('local')->exists($certPath)) {
            throw new SignatureException('Certificate file not found.');
        }

        return Storage::disk('local')->get($certPath);
    }

    /**
     * Get the private key PEM.
     *
     * @throws SignatureException
     */
    public function getPrivateKey(): string
    {
        $keyPath = 'certificates/private_key.pem';

        if (!Storage::disk('local')->exists($keyPath)) {
            throw new SignatureException('Private key file not found.');
        }

        return Storage::disk('local')->get($keyPath);
    }

    /**
     * Get the certificate chain PEM (array of certs).
     *
     * @return string[]
     */
    public function getCertificateChain(): array
    {
        $chainPath = 'certificates/chain.pem';

        if (!Storage::disk('local')->exists($chainPath)) {
            return [];
        }

        $chainPem = Storage::disk('local')->get($chainPath);
        preg_match_all(
            '/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s',
            $chainPem,
            $matches
        );

        return $matches[0] ?? [];
    }

    /**
     * Check if the certificate is about to expire (within N days).
     */
    public function isExpiringSoon(int $daysThreshold = 30): bool
    {
        $settings = CompanySetting::first();

        if (!$settings || !$settings->certificate_expires_at) {
            return true;
        }

        return now()->addDays($daysThreshold)->greaterThanOrEqualTo($settings->certificate_expires_at);
    }

    /**
     * Check if the certificate is currently valid.
     */
    public function isValid(): bool
    {
        $settings = CompanySetting::first();

        if (!$settings || !$settings->certificate_expires_at) {
            return false;
        }

        return now()->lessThan($settings->certificate_expires_at);
    }

    /**
     * Get the raw DER-encoded certificate (for XAdES digest).
     *
     * @throws SignatureException
     */
    public function getSigningCertificateDer(): string
    {
        $pem = $this->getSigningCertificate();
        $cleaned = preg_replace('/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s/', '', $pem);

        if ($cleaned === null || $cleaned === '') {
            throw new SignatureException('Failed to extract DER from certificate.');
        }

        $der = base64_decode($cleaned, true);
        if ($der === false) {
            throw new SignatureException('Failed to decode certificate DER.');
        }

        return $der;
    }

    /**
     * Get X.509 serial number in hex.
     *
     * @throws SignatureException
     */
    public function getSerialNumber(): string
    {
        $pem = $this->getSigningCertificate();
        $parsed = openssl_x509_parse($pem);

        if ($parsed === false) {
            throw new SignatureException('Failed to parse certificate.');
        }

        return $parsed['serialNumberHex'] ?? '';
    }

    /**
     * Get X.509 issuer distinguished name.
     *
     * @throws SignatureException
     */
    public function getIssuerDN(): string
    {
        $pem = $this->getSigningCertificate();
        $parsed = openssl_x509_parse($pem);

        if ($parsed === false) {
            throw new SignatureException('Failed to parse certificate.');
        }

        $issuer = $parsed['issuer'] ?? [];
        $parts = [];

        foreach (['CN', 'O', 'OU', 'L', 'ST', 'C'] as $field) {
            if (!empty($issuer[$field])) {
                $parts[] = "{$field}={$issuer[$field]}";
            }
        }

        return implode(',', $parts);
    }
}
