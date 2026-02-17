<?php

namespace Tests\Unit;

use App\Enums\DocumentTypeCode;
use App\Enums\IdentifierType;
use App\Enums\TaxTypeCode;
use App\Enums\AmountTypeCode;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    // --- DocumentTypeCode ---

    public function test_document_type_code_facture(): void
    {
        $this->assertEquals('I-11', DocumentTypeCode::FACTURE->value);
        $this->assertNotEmpty(DocumentTypeCode::FACTURE->label());
    }

    public function test_document_type_code_all_cases_have_labels(): void
    {
        foreach (DocumentTypeCode::cases() as $case) {
            $this->assertNotEmpty($case->label(), "DocumentTypeCode {$case->value} should have a label");
        }
    }

    // --- IdentifierType ---

    public function test_identifier_type_values(): void
    {
        $this->assertEquals('I-01', IdentifierType::MATRICULE_FISCAL->value);
        $this->assertEquals('I-02', IdentifierType::CIN->value);
        $this->assertEquals('I-03', IdentifierType::CARTE_SEJOUR->value);
        $this->assertEquals('I-04', IdentifierType::OTHER->value);
    }

    public function test_identifier_type_has_validation_pattern(): void
    {
        foreach (IdentifierType::cases() as $type) {
            $pattern = $type->validationPattern();
            $this->assertNotEmpty($pattern, "IdentifierType {$type->value} should have a validation pattern");
        }
    }

    // --- TaxTypeCode ---

    public function test_tax_type_code_values(): void
    {
        $this->assertEquals('I-1601', TaxTypeCode::DROIT_TIMBRE->value);
        $this->assertEquals('I-1602', TaxTypeCode::TVA->value);
        $this->assertEquals('I-1603', TaxTypeCode::OTHER->value);
    }

    public function test_tax_type_code_labels(): void
    {
        foreach (TaxTypeCode::cases() as $case) {
            $this->assertNotEmpty($case->label());
        }
    }

    // --- AmountTypeCode ---

    public function test_amount_type_code_has_expected_values(): void
    {
        $this->assertEquals('I-171', AmountTypeCode::LINE_NET->value);
    }

    public function test_amount_type_code_count(): void
    {
        // Blueprint specifies I-171 through I-188
        $this->assertGreaterThanOrEqual(5, count(AmountTypeCode::cases()));
    }
}
