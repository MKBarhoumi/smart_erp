<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DocumentTypeCode;
use App\Enums\OldInvoiceStatus;
use App\Models\Customer;
use App\Models\OldInvoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;



    private function createOldInvoice(): array
    {
        $customer = Customer::create([
            'name' => 'Client Test',
            'identifier_type' => 'I-01',
            'identifier_value' => '1234567A/B/M/000',
        ]);

        $user = User::factory()->create();

        $oldinvoice = OldInvoice::create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'oldinvoice_number' => 'FAC-001',
            'document_identifier' => 'FAC-001',
            'document_type_code' => DocumentTypeCode::FACTURE,
            'oldinvoice_date' => now(),
            'due_date' => now()->addMonth(),
            'status' => OldInvoiceStatus::ACCEPTED,
            'total_ht' => '1000.000',
            'total_tva' => '190.000',
            'total_ttc' => '1190.000',
            'timbre_fiscal' => '1.000',
        ]);

        return [$oldinvoice, $user];
    }

    public function test_payment_can_be_created(): void
    {
        [$oldinvoice, $user] = $this->createOldInvoice();

        $payment = Payment::create([
            'oldinvoice_id' => $oldinvoice->id,
            'created_by' => $user->id,
            'amount' => '500.000',
            'payment_date' => now(),
            'method' => 'bank_transfer',
            'reference' => 'VIR-2025-001',
            'notes' => 'Partial payment',
        ]);

        $this->assertDatabaseHas('payments', [
            'oldinvoice_id' => $oldinvoice->id,
            'amount' => '500.000',
            'method' => 'bank_transfer',
        ]);
    }

    public function test_payment_belongs_to_oldinvoice(): void
    {
        [$oldinvoice, $user] = $this->createOldInvoice();

        $payment = Payment::create([
            'oldinvoice_id' => $oldinvoice->id,
            'created_by' => $user->id,
            'amount' => '500.000',
            'payment_date' => now(),
            'method' => 'cash',
        ]);

        $this->assertEquals($oldinvoice->id, $payment->oldinvoice->id);
    }

    public function test_oldinvoice_has_many_payments(): void
    {
        [$oldinvoice, $user] = $this->createOldInvoice();

        Payment::create([
            'oldinvoice_id' => $oldinvoice->id,
            'created_by' => $user->id,
            'amount' => '500.000',
            'payment_date' => now(),
            'method' => 'bank_transfer',
        ]);

        Payment::create([
            'oldinvoice_id' => $oldinvoice->id,
            'created_by' => $user->id,
            'amount' => '691.000',
            'payment_date' => now(),
            'method' => 'cheque',
            'reference' => 'CHQ-001',
        ]);

        $this->assertCount(2, $oldinvoice->fresh()->payments);
    }

    public function test_payment_total_matches_oldinvoice(): void
    {
        [$oldinvoice, $user] = $this->createOldInvoice();

        Payment::create([
            'oldinvoice_id' => $oldinvoice->id,
            'created_by' => $user->id,
            'amount' => '500.000',
            'payment_date' => now(),
            'method' => 'bank_transfer',
        ]);

        Payment::create([
            'oldinvoice_id' => $oldinvoice->id,
            'created_by' => $user->id,
            'amount' => '691.000',
            'payment_date' => now(),
            'method' => 'cash',
        ]);

        $totalPaid = $oldinvoice->fresh()->payments->sum('amount');

        $this->assertEquals(1191.0, $totalPaid);
    }

    public function test_payment_can_be_deleted(): void
    {
        [$oldinvoice, $user] = $this->createOldInvoice();

        $payment = Payment::create([
            'oldinvoice_id' => $oldinvoice->id,
            'created_by' => $user->id,
            'amount' => '500.000',
            'payment_date' => now(),
            'method' => 'bank_transfer',
        ]);

        $paymentId = $payment->id;
        $payment->delete();

        $this->assertDatabaseMissing('payments', ['id' => $paymentId]);
    }
}
