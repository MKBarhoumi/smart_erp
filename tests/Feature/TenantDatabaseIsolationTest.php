<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

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
});

afterEach(function () {
    $this->tenant1->delete();
    $this->tenant2->delete();
});

test('database isolation: users are isolated between tenants', function () {
    tenancy()->initialize($this->tenant1);

    User::create([
        'name' => 'User 1',
        'email' => 'user1@tenant1.com',
        'password' => bcrypt('password'),
    ]);

    tenancy()->end();

    tenancy()->initialize($this->tenant2);

    User::create([
        'name' => 'User 2',
        'email' => 'user2@tenant2.com',
        'password' => bcrypt('password'),
    ]);

    tenancy()->end();

    tenancy()->initialize($this->tenant1);

    expect(User::where('email', 'user1@tenant1.com')->exists())->toBeTrue();
    expect(User::where('email', 'user2@tenant2.com')->exists())->toBeFalse();

    tenancy()->end();

    tenancy()->initialize($this->tenant2);

    expect(User::where('email', 'user2@tenant2.com')->exists())->toBeTrue();
    expect(User::where('email', 'user1@tenant1.com')->exists())->toBeFalse();

    tenancy()->end();
});

test('database isolation: customers are isolated between tenants', function () {
    tenancy()->initialize($this->tenant1);

    Customer::create([
        'name' => 'Customer 1',
        'email' => 'customer1@tenant1.com',
        'phone' => '123456789',
        'address' => 'Address 1',
    ]);

    tenancy()->end();

    tenancy()->initialize($this->tenant2);

    Customer::create([
        'name' => 'Customer 2',
        'email' => 'customer2@tenant2.com',
        'phone' => '987654321',
        'address' => 'Address 2',
    ]);

    tenancy()->end();

    tenancy()->initialize($this->tenant1);

    expect(Customer::where('email', 'customer1@tenant1.com')->exists())->toBeTrue();
    expect(Customer::where('email', 'customer2@tenant2.com')->exists())->toBeFalse();

    tenancy()->end();

    tenancy()->initialize($this->tenant2);

    expect(Customer::where('email', 'customer2@tenant2.com')->exists())->toBeTrue();
    expect(Customer::where('email', 'customer1@tenant1.com')->exists())->toBeFalse();

    tenancy()->end();
});

test('database isolation: products are isolated between tenants', function () {
    tenancy()->initialize($this->tenant1);

    Product::create([
        'name' => 'Product 1',
        'description' => 'Description 1',
        'price' => 100.00,
        'quantity' => 10,
    ]);

    tenancy()->end();

    tenancy()->initialize($this->tenant2);

    Product::create([
        'name' => 'Product 2',
        'description' => 'Description 2',
        'price' => 200.00,
        'quantity' => 20,
    ]);

    tenancy()->end();

    tenancy()->initialize($this->tenant1);

    expect(Product::where('name', 'Product 1')->exists())->toBeTrue();
    expect(Product::where('name', 'Product 2')->exists())->toBeFalse();

    tenancy()->end();

    tenancy()->initialize($this->tenant2);

    expect(Product::where('name', 'Product 2')->exists())->toBeTrue();
    expect(Product::where('name', 'Product 1')->exists())->toBeFalse();

    tenancy()->end();
});

test('database isolation: database connections are separate', function () {
    tenancy()->initialize($this->tenant1);

    $db1 = DB::connection()->getDatabaseName();

    tenancy()->end();

    tenancy()->initialize($this->tenant2);

    $db2 = DB::connection()->getDatabaseName();

    tenancy()->end();

    expect($db1)->not->toBe($db2);
});

test('database isolation: queries do not leak between tenants', function () {
    tenancy()->initialize($this->tenant1);

    User::create([
        'name' => 'User 1',
        'email' => 'user1@tenant1.com',
        'password' => bcrypt('password'),
    ]);

    $count1 = User::count();

    tenancy()->end();

    tenancy()->initialize($this->tenant2);

    User::create([
        'name' => 'User 2',
        'email' => 'user2@tenant2.com',
        'password' => bcrypt('password'),
    ]);

    $count2 = User::count();

    tenancy()->end();

    expect($count1)->toBe(1);
    expect($count2)->toBe(1);
});

test('database isolation: transactions are isolated between tenants', function () {
    tenancy()->initialize($this->tenant1);

    DB::beginTransaction();

    User::create([
        'name' => 'User 1',
        'email' => 'user1@tenant1.com',
        'password' => bcrypt('password'),
    ]);

    expect(User::where('email', 'user1@tenant1.com')->exists())->toBeTrue();

    DB::rollBack();

    expect(User::where('email', 'user1@tenant1.com')->exists())->toBeFalse();

    tenancy()->end();

    tenancy()->initialize($this->tenant2);

    DB::beginTransaction();

    User::create([
        'name' => 'User 2',
        'email' => 'user2@tenant2.com',
        'password' => bcrypt('password'),
    ]);

    expect(User::where('email', 'user2@tenant2.com')->exists())->toBeTrue();

    DB::rollBack();

    expect(User::where('email', 'user2@tenant2.com')->exists())->toBeFalse();

    tenancy()->end();
});