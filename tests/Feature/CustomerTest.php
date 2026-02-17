<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;



    public function test_customer_can_be_created(): void
    {
        $customer = Customer::create([
            'name' => 'Société Test SARL',
            'identifier_type' => 'I-01',
            'identifier_value' => '1234567A/B/M/000',
            'street' => '10 Rue de Tunis',
            'city' => 'Tunis',
            'postal_code' => '1000',
            'phone' => '+216 71 123 456',
            'email' => 'contact@test.tn',
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Société Test SARL',
            'identifier_value' => '1234567A/B/M/000',
        ]);
        $this->assertNotNull($customer->id);
    }

    public function test_customer_identifier_type_is_enum(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'identifier_type' => 'I-01',
            'identifier_value' => '1234567A/B/M/000',
        ]);

        $this->assertInstanceOf(\App\Enums\IdentifierType::class, $customer->identifier_type);
    }

    public function test_customer_has_many_invoices(): void
    {
        $customer = Customer::create([
            'name' => 'Test',
            'identifier_type' => 'I-01',
            'identifier_value' => '1234567A/B/M/000',
        ]);

        $this->assertCount(0, $customer->invoices);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $customer->invoices);
    }

    public function test_customer_can_be_updated(): void
    {
        $customer = Customer::create([
            'name' => 'Old Name',
            'identifier_type' => 'I-01',
            'identifier_value' => '1234567A/B/M/000',
        ]);

        $customer->update(['name' => 'New Name']);

        $this->assertEquals('New Name', $customer->fresh()->name);
    }

    public function test_customer_can_be_deleted(): void
    {
        $customer = Customer::create([
            'name' => 'To Delete',
            'identifier_type' => 'I-01',
            'identifier_value' => '1234567A/B/M/000',
        ]);

        $id = $customer->id;
        $customer->forceDelete();

        $this->assertDatabaseMissing('customers', ['id' => $id]);
    }

    public function test_customer_search_by_name(): void
    {
        Customer::create([
            'name' => 'Alpha Corp',
            'identifier_type' => 'I-01',
            'identifier_value' => '1234567A/B/M/000',
        ]);
        Customer::create([
            'name' => 'Beta Industries',
            'identifier_type' => 'I-01',
            'identifier_value' => '7654321B/C/M/000',
        ]);

        $results = Customer::where('name', 'like', '%Alpha%')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Alpha Corp', $results->first()->name);
    }
}
