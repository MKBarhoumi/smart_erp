<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Services\Security\SensitiveDataService;

class SensitiveDataServiceTest extends TestCase
{
    public function test_hide_sensitive_data_in_array(): void
    {
        $service = new SensitiveDataService();
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'api_key' => 'sk_test_1234567890',
        ];

        $cleaned = $service->hideSensitiveData($data);

        $this->assertEquals('John Doe', $cleaned['name']);
        $this->assertEquals('john@example.com', $cleaned['email']);
        $this->assertNotEquals('secret123', $cleaned['password']);
        $this->assertNotEquals('sk_test_1234567890', $cleaned['api_key']);
    }

    public function test_mask_value(): void
    {
        $service = new SensitiveDataService();
        $value = 'secret123';

        $masked = $service->maskValue($value);

        $this->assertNotEquals($value, $masked);
        $this->assertStringEndsWith('123', $masked);
    }

    public function test_is_sensitive_field(): void
    {
        $service = new SensitiveDataService();

        $this->assertTrue($service->isSensitiveField('password'));
        $this->assertTrue($service->isSensitiveField('api_key'));
        $this->assertTrue($service->isSensitiveField('stripe_secret'));
        $this->assertFalse($service->isSensitiveField('name'));
        $this->assertFalse($service->isSensitiveField('email'));
    }

    public function test_add_sensitive_field(): void
    {
        $service = new SensitiveDataService();
        $service->addSensitiveField('custom_secret');

        $this->assertTrue($service->isSensitiveField('custom_secret'));
    }
}