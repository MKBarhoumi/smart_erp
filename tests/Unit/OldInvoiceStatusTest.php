<?php

namespace Tests\Unit;

use App\Enums\OldInvoiceStatus;
use PHPUnit\Framework\TestCase;

class OldInvoiceStatusTest extends TestCase
{
    public function test_draft_can_transition_to_validated(): void
    {
        $this->assertTrue(OldInvoiceStatus::DRAFT->canTransitionTo(OldInvoiceStatus::VALIDATED));
    }

    public function test_validated_can_transition_to_signed(): void
    {
        $this->assertTrue(OldInvoiceStatus::VALIDATED->canTransitionTo(OldInvoiceStatus::SIGNED));
    }

    public function test_signed_can_transition_to_submitted(): void
    {
        $this->assertTrue(OldInvoiceStatus::SIGNED->canTransitionTo(OldInvoiceStatus::SUBMITTED));
    }

    public function test_submitted_can_transition_to_accepted(): void
    {
        $this->assertTrue(OldInvoiceStatus::SUBMITTED->canTransitionTo(OldInvoiceStatus::ACCEPTED));
    }

    public function test_submitted_can_transition_to_rejected(): void
    {
        $this->assertTrue(OldInvoiceStatus::SUBMITTED->canTransitionTo(OldInvoiceStatus::REJECTED));
    }

    public function test_accepted_can_transition_to_archived(): void
    {
        $this->assertTrue(OldInvoiceStatus::ACCEPTED->canTransitionTo(OldInvoiceStatus::ARCHIVED));
    }

    public function test_draft_cannot_skip_to_signed(): void
    {
        $this->assertFalse(OldInvoiceStatus::DRAFT->canTransitionTo(OldInvoiceStatus::SIGNED));
    }

    public function test_draft_cannot_skip_to_submitted(): void
    {
        $this->assertFalse(OldInvoiceStatus::DRAFT->canTransitionTo(OldInvoiceStatus::SUBMITTED));
    }

    public function test_accepted_cannot_go_back_to_draft(): void
    {
        $this->assertFalse(OldInvoiceStatus::ACCEPTED->canTransitionTo(OldInvoiceStatus::DRAFT));
    }

    public function test_archived_cannot_transition(): void
    {
        $this->assertFalse(OldInvoiceStatus::ARCHIVED->canTransitionTo(OldInvoiceStatus::DRAFT));
        $this->assertFalse(OldInvoiceStatus::ARCHIVED->canTransitionTo(OldInvoiceStatus::VALIDATED));
    }

    public function test_all_statuses_have_labels(): void
    {
        foreach (OldInvoiceStatus::cases() as $status) {
            $label = $status->label();
            $this->assertNotEmpty($label, "Status {$status->value} should have a label");
            $this->assertIsString($label);
        }
    }
}
