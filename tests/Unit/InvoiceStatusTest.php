<?php

namespace Tests\Unit;

use App\Enums\InvoiceStatus;
use PHPUnit\Framework\TestCase;

class InvoiceStatusTest extends TestCase
{
    public function test_draft_can_transition_to_validated(): void
    {
        $this->assertTrue(InvoiceStatus::DRAFT->canTransitionTo(InvoiceStatus::VALIDATED));
    }

    public function test_validated_can_transition_to_signed(): void
    {
        $this->assertTrue(InvoiceStatus::VALIDATED->canTransitionTo(InvoiceStatus::SIGNED));
    }

    public function test_signed_can_transition_to_submitted(): void
    {
        $this->assertTrue(InvoiceStatus::SIGNED->canTransitionTo(InvoiceStatus::SUBMITTED));
    }

    public function test_submitted_can_transition_to_accepted(): void
    {
        $this->assertTrue(InvoiceStatus::SUBMITTED->canTransitionTo(InvoiceStatus::ACCEPTED));
    }

    public function test_submitted_can_transition_to_rejected(): void
    {
        $this->assertTrue(InvoiceStatus::SUBMITTED->canTransitionTo(InvoiceStatus::REJECTED));
    }

    public function test_accepted_can_transition_to_archived(): void
    {
        $this->assertTrue(InvoiceStatus::ACCEPTED->canTransitionTo(InvoiceStatus::ARCHIVED));
    }

    public function test_draft_cannot_skip_to_signed(): void
    {
        $this->assertFalse(InvoiceStatus::DRAFT->canTransitionTo(InvoiceStatus::SIGNED));
    }

    public function test_draft_cannot_skip_to_submitted(): void
    {
        $this->assertFalse(InvoiceStatus::DRAFT->canTransitionTo(InvoiceStatus::SUBMITTED));
    }

    public function test_accepted_cannot_go_back_to_draft(): void
    {
        $this->assertFalse(InvoiceStatus::ACCEPTED->canTransitionTo(InvoiceStatus::DRAFT));
    }

    public function test_archived_cannot_transition(): void
    {
        $this->assertFalse(InvoiceStatus::ARCHIVED->canTransitionTo(InvoiceStatus::DRAFT));
        $this->assertFalse(InvoiceStatus::ARCHIVED->canTransitionTo(InvoiceStatus::VALIDATED));
    }

    public function test_all_statuses_have_labels(): void
    {
        foreach (InvoiceStatus::cases() as $status) {
            $label = $status->label();
            $this->assertNotEmpty($label, "Status {$status->value} should have a label");
            $this->assertIsString($label);
        }
    }
}
