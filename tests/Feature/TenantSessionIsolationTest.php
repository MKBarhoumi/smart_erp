<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;

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

    $this->user1 = User::create([
        'name' => 'User 1',
        'email' => 'user1@tenant1.com',
        'password' => bcrypt('password'),
    ]);

    tenancy()->end();

    tenancy()->initialize($this->tenant2);

    $this->user2 = User::create([
        'name' => 'User 2',
        'email' => 'user2@tenant2.com',
        'password' => bcrypt('password'),
    ]);

    tenancy()->end();
});

afterEach(function () {
    $this->tenant1->delete();
    $this->tenant2->delete();
});

test('session isolation: sessions are isolated between tenants', function () {
    $this->actingAs($this->user1)
        ->get('http://tenant1.localhost/dashboard')
        ->assertStatus(200);

    $this->actingAs($this->user2)
        ->get('http://tenant2.localhost/dashboard')
        ->assertStatus(200);
});

test('session isolation: login on tenant1 does not affect tenant2', function () {
    $this->post('http://tenant1.localhost/login', [
        'email' => 'user1@tenant1.com',
        'password' => 'password',
    ])->assertRedirect();

    $this->get('http://tenant1.localhost/dashboard')
        ->assertStatus(200);

    $this->get('http://tenant2.localhost/dashboard')
        ->assertStatus(401);
});

test('session isolation: login on tenant2 does not affect tenant1', function () {
    $this->post('http://tenant2.localhost/login', [
        'email' => 'user2@tenant2.com',
        'password' => 'password',
    ])->assertRedirect();

    $this->get('http://tenant2.localhost/dashboard')
        ->assertStatus(200);

    $this->get('http://tenant1.localhost/dashboard')
        ->assertStatus(401);
});

test('session isolation: session data is isolated per tenant', function () {
    $this->actingAs($this->user1)
        ->withSession(['key' => 'value1'])
        ->get('http://tenant1.localhost/test-tenancy')
        ->assertStatus(200);

    $this->actingAs($this->user2)
        ->withSession(['key' => 'value2'])
        ->get('http://tenant2.localhost/test-tenancy')
        ->assertStatus(200);
});

test('session isolation: cookies are domain-specific', function () {
    $response1 = $this->post('http://tenant1.localhost/login', [
        'email' => 'user1@tenant1.com',
        'password' => 'password',
    ]);

    $cookies1 = $response1->headers->getCookies();

    $response2 = $this->post('http://tenant2.localhost/login', [
        'email' => 'user2@tenant2.com',
        'password' => 'password',
    ]);

    $cookies2 = $response2->headers->getCookies();

    expect($cookies1)->not->toBeEmpty();
    expect($cookies2)->not->toBeEmpty();
});

test('session isolation: logout on tenant1 does not affect tenant2', function () {
    $this->post('http://tenant1.localhost/login', [
        'email' => 'user1@tenant1.com',
        'password' => 'password',
    ])->assertRedirect();

    $this->post('http://tenant2.localhost/login', [
        'email' => 'user2@tenant2.com',
        'password' => 'password',
    ])->assertRedirect();

    $this->post('http://tenant1.localhost/logout')
        ->assertRedirect();

    $this->get('http://tenant1.localhost/dashboard')
        ->assertStatus(401);

    $this->get('http://tenant2.localhost/dashboard')
        ->assertStatus(200);
});

test('session isolation: session timeout is per tenant', function () {
    $this->actingAs($this->user1)
        ->get('http://tenant1.localhost/dashboard')
        ->assertStatus(200);

    $this->actingAs($this->user2)
        ->get('http://tenant2.localhost/dashboard')
        ->assertStatus(200);
});

test('session isolation: CSRF tokens are per tenant', function () {
    $response1 = $this->get('http://tenant1.localhost/dashboard');
    $token1 = $this->extractCsrfToken($response1);

    $response2 = $this->get('http://tenant2.localhost/dashboard');
    $token2 = $this->extractCsrfToken($response2);

    expect($token1)->not->toBe($token2);
});

test('session isolation: flash messages are isolated per tenant', function () {
    $this->actingAs($this->user1)
        ->withSession(['_flash' => ['message' => 'Message 1']])
        ->get('http://tenant1.localhost/dashboard')
        ->assertStatus(200);

    $this->actingAs($this->user2)
        ->withSession(['_flash' => ['message' => 'Message 2']])
        ->get('http://tenant2.localhost/dashboard')
        ->assertStatus(200);
});

test('session isolation: authenticated user is isolated per tenant', function () {
    $this->actingAs($this->user1)
        ->get('http://tenant1.localhost/api/user')
        ->assertJson([
            'email' => 'user1@tenant1.com',
        ]);

    $this->actingAs($this->user2)
        ->get('http://tenant2.localhost/api/user')
        ->assertJson([
            'email' => 'user2@tenant2.com',
        ]);
});

test('session isolation: session ID is different per tenant', function () {
    $response1 = $this->get('http://tenant1.localhost/test-tenancy');
    $sessionId1 = $response1->getSession()->getId();

    $response2 = $this->get('http://tenant2.localhost/test-tenancy');
    $sessionId2 = $response2->getSession()->getId();

    expect($sessionId1)->not->toBe($sessionId2);
});