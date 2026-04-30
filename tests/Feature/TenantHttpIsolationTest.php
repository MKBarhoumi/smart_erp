<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->tenant1 = Tenant::create([
        'id' => 'tenant-1',
        'name' => 'Tenant 1',
    ]);

    $this->tenant2 = Tenant::create([
        'id' => 'tenant-2',
        'name' => 'Tenant 2',
    ]);

    $this->tenant1->domains()->create([
        'domain' => 'tenant1.localhost',
    ]);

    $this->tenant2->domains()->create([
        'domain' => 'tenant2.localhost',
    ]);

    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant1->id, $this->tenant2->id]]);

    tenancy()->initialize($this->tenant1);

    User::create([
        'name' => 'User 1',
        'email' => 'user1@tenant1.com',
        'password' => bcrypt('password'),
    ]);

    Customer::create([
        'name' => 'Customer 1',
        'email' => 'customer1@tenant1.com',
        'phone' => '123456789',
        'address' => 'Address 1',
    ]);

    tenancy()->end();

    tenancy()->initialize($this->tenant2);

    User::create([
        'name' => 'User 2',
        'email' => 'user2@tenant2.com',
        'password' => bcrypt('password'),
    ]);

    Customer::create([
        'name' => 'Customer 2',
        'email' => 'customer2@tenant2.com',
        'phone' => '987654321',
        'address' => 'Address 2',
    ]);

    tenancy()->end();
});

afterEach(function () {
    $this->tenant1->delete();
    $this->tenant2->delete();
});

test('HTTP isolation: different tenant domains return different data', function () {
    $response1 = $this->get('http://tenant1.localhost/api/customers');
    $response2 = $this->get('http://tenant2.localhost/api/customers');

    $data1 = $response1->json();
    $data2 = $response2->json();

    expect($data1)->not->toBe($data2);
});

test('HTTP isolation: tenant1 cannot access tenant2 data', function () {
    $response = $this->get('http://tenant1.localhost/api/customers');

    $data = $response->json();

    expect($data)->toHaveCount(1);
    expect($data[0]['email'])->toBe('customer1@tenant1.com');
});

test('HTTP isolation: tenant2 cannot access tenant1 data', function () {
    $response = $this->get('http://tenant2.localhost/api/customers');

    $data = $response->json();

    expect($data)->toHaveCount(1);
    expect($data[0]['email'])->toBe('customer2@tenant2.com');
});

test('HTTP isolation: test-tenancy endpoint returns correct tenant info', function () {
    $response1 = $this->get('http://tenant1.localhost/test-tenancy');
    $response2 = $this->get('http://tenant2.localhost/test-tenancy');

    $data1 = $response1->json();
    $data2 = $response2->json();

    expect($data1['tenant']['id'])->toBe('tenant-1');
    expect($data1['tenant']['name'])->toBe('Tenant 1');

    expect($data2['tenant']['id'])->toBe('tenant-2');
    expect($data2['tenant']['name'])->toBe('Tenant 2');
});

test('HTTP isolation: requests to invalid tenant domain fail', function () {
    $response = $this->get('http://invalid.localhost/test-tenancy');

    $response->assertStatus(404);
});

test('HTTP isolation: central routes work on central domain', function () {
    $response = $this->get('http://localhost/');

    $response->assertStatus(200);
});

test('HTTP isolation: tenant registration works on central domain', function () {
    $response = $this->post('http://localhost/api/register-tenant', [
        'name' => 'New Tenant',
        'domain' => 'newtenant.localhost',
        'email' => 'admin@newtenant.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(201);
    $response->assertJson([
        'message' => 'Tenant created successfully',
    ]);
});

test('HTTP isolation: tenant-specific routes are protected', function () {
    $response = $this->get('http://localhost/dashboard');

    $response->assertStatus(401);
});

test('HTTP isolation: authenticated requests are isolated per tenant', function () {
    $user1 = User::where('email', 'user1@tenant1.com')->first();
    $user2 = User::where('email', 'user2@tenant2.com')->first();

    $this->actingAs($user1)
        ->get('http://tenant1.localhost/api/customers')
        ->assertStatus(200);

    $this->actingAs($user2)
        ->get('http://tenant2.localhost/api/customers')
        ->assertStatus(200);
});