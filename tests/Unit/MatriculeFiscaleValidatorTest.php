<?php

namespace Tests\Unit;

use App\Services\MatriculeFiscaleValidator;
use PHPUnit\Framework\TestCase;

class MatriculeFiscaleValidatorTest extends TestCase
{
    private MatriculeFiscaleValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new MatriculeFiscaleValidator();
    }

    // --- Matricule Fiscale (I-01) Tests ---

    public function test_valid_mf_full_format(): void
    {
        $result = $this->validator->validate('I-01', '1234567ABC000');
        $this->assertTrue($result);
    }

    public function test_valid_mf_different_letters(): void
    {
        $result = $this->validator->validate('I-01', '0000001ZNP000');
        $this->assertTrue($result);
    }

    public function test_invalid_mf_wrong_length(): void
    {
        $result = $this->validator->validate('I-01', '123456A/B/C/000');
        $this->assertFalse($result);
    }

    public function test_invalid_mf_forbidden_letters(): void
    {
        // I and O are not allowed in MF letter position
        $result = $this->validator->validate('I-01', '1234567I/B/C/000');
        $this->assertFalse($result);
    }

    public function test_invalid_mf_wrong_category(): void
    {
        // Category must be A, B, D, N, or P
        $result = $this->validator->validate('I-01', '1234567A/X/C/000');
        $this->assertFalse($result);
    }

    public function test_invalid_mf_wrong_person_code(): void
    {
        // Person code must be C, M, N, or P
        $result = $this->validator->validate('I-01', '1234567A/B/Z/000');
        $this->assertFalse($result);
    }

    public function test_invalid_mf_wrong_establishment(): void
    {
        // Secondary establishment must be 3 digits
        $result = $this->validator->validate('I-01', '1234567A/B/C/00');
        $this->assertFalse($result);
    }

    // --- CIN (I-02) Tests ---

    public function test_valid_cin(): void
    {
        $result = $this->validator->validate('I-02', '12345678');
        $this->assertTrue($result);
    }

    public function test_invalid_cin_too_short(): void
    {
        $result = $this->validator->validate('I-02', '1234567');
        $this->assertFalse($result);
    }

    public function test_invalid_cin_with_letters(): void
    {
        $result = $this->validator->validate('I-02', '1234567A');
        $this->assertFalse($result);
    }

    // --- Carte de séjour (I-03) Tests ---

    public function test_valid_carte_sejour(): void
    {
        $result = $this->validator->validate('I-03', '123456789');
        $this->assertTrue($result);
    }

    // --- Other (I-04) Tests ---

    public function test_valid_other_identifier(): void
    {
        $result = $this->validator->validate('I-04', 'TN12345678');
        $this->assertTrue($result);
    }

    // --- Edge Cases ---

    public function test_empty_value_is_invalid(): void
    {
        $result = $this->validator->validate('I-01', '');
        $this->assertFalse($result);
    }

    public function test_invalid_identifier_type(): void
    {
        $result = $this->validator->validate('X-99', 'anything');
        $this->assertFalse($result);
    }
}
