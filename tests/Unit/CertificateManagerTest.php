<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CertificateManager;
use App\Exceptions\SignatureException;
use App\Models\CompanySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateManagerTest extends TestCase
{
    use RefreshDatabase;

    private CertificateManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new CertificateManager();
        Storage::fake('local');
    }

    private function createSettings(array $overrides = []): void
    {
        CompanySetting::create(array_merge([
            'company_name' => 'Test Co',
            'matricule_fiscal' => '1234567A/B/M/000',
            'category_type' => 'A',
            'person_type' => 'M',
            'city' => 'Tunis',
        ], $overrides));
    }

    public function test_is_valid_returns_false_when_no_settings(): void
    {
        $this->assertFalse($this->manager->isValid());
    }

    public function test_is_valid_returns_false_when_no_certificate_expiry(): void
    {
        $this->createSettings(['certificate_expires_at' => null]);

        $this->assertFalse($this->manager->isValid());
    }

    public function test_is_valid_returns_true_for_future_expiry(): void
    {
        $this->createSettings(['certificate_expires_at' => now()->addYear()]);

        $this->assertTrue($this->manager->isValid());
    }

    public function test_is_valid_returns_false_for_expired_certificate(): void
    {
        $this->createSettings(['certificate_expires_at' => now()->subDay()]);

        $this->assertFalse($this->manager->isValid());
    }

    public function test_is_expiring_soon_returns_true_within_threshold(): void
    {
        $this->createSettings(['certificate_expires_at' => now()->addDays(15)]);

        $this->assertTrue($this->manager->isExpiringSoon(30));
    }

    public function test_is_expiring_soon_returns_false_when_far_from_expiry(): void
    {
        $this->createSettings(['certificate_expires_at' => now()->addYear()]);

        $this->assertFalse($this->manager->isExpiringSoon(30));
    }

    public function test_is_expiring_soon_returns_true_when_no_settings(): void
    {
        $this->assertTrue($this->manager->isExpiringSoon());
    }

    public function test_get_signing_certificate_throws_when_not_configured(): void
    {
        $this->createSettings(['certificate_file' => null]);

        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('No signing certificate configured.');

        $this->manager->getSigningCertificate();
    }

    public function test_get_signing_certificate_throws_when_file_missing(): void
    {
        $this->createSettings(['certificate_file' => 'certificates/default/certificate.pem']);

        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('Certificate file not found.');

        $this->manager->getSigningCertificate();
    }

    public function test_get_signing_certificate_returns_pem(): void
    {
        $pemContent = "-----BEGIN CERTIFICATE-----\nMIIBkTCB+wIJALRiMLAh...fake...\n-----END CERTIFICATE-----";

        Storage::disk('local')->put('certificates/default/certificate.pem', $pemContent);
        $this->createSettings(['certificate_file' => 'certificates/default/certificate.pem']);

        $result = $this->manager->getSigningCertificate();

        $this->assertEquals($pemContent, $result);
    }

    public function test_get_private_key_throws_when_file_missing(): void
    {
        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('Private key file not found.');

        $this->manager->getPrivateKey();
    }

    public function test_get_certificate_chain_returns_empty_array_when_no_chain(): void
    {
        $chain = $this->manager->getCertificateChain();

        $this->assertIsArray($chain);
        $this->assertEmpty($chain);
    }

    public function test_get_certificate_chain_parses_multiple_certs(): void
    {
        $chain = "-----BEGIN CERTIFICATE-----\nAAA\n-----END CERTIFICATE-----\n-----BEGIN CERTIFICATE-----\nBBB\n-----END CERTIFICATE-----";

        Storage::disk('local')->put('certificates/chain.pem', $chain);

        $result = $this->manager->getCertificateChain();

        $this->assertCount(2, $result);
    }
}
