<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;
use App\Services\PlanLimitsService;
use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    Artisan::call('migrate:fresh');
    Artisan::call('db:seed', ['--class' => 'PlansSeeder']);

    $this->freePlan = Plan::where('slug', 'free')->first();
    $this->proPlan = Plan::where('slug', 'pro')->first();
    $this->enterprisePlan = Plan::where('slug', 'enterprise')->first();

    $this->tenant = Tenant::create([
        'id' => 'test-tenant',
        'name' => 'Test Tenant',
        'plan_id' => $this->freePlan->id,
    ]);

    $this->tenant->domains()->create([
        'domain' => 'test.localhost',
    ]);

    Artisan::call('tenants:migrate', ['--tenants' => [$this->tenant->id]]);

    tenancy()->initialize($this->tenant);

    $this->service = new PlanLimitsService();
});

afterEach(function () {
    tenancy()->end();
});

test('service can check if tenant can create user', function () {
    expect($this->service->canCreateUser($this->tenant))->toBeTrue();

    User::factory()->count(2)->create();

    expect($this->service->canCreateUser($this->tenant))->toBeFalse();
});

test('service can check if tenant can create customer', function () {
    expect($this->service->canCreateCustomer($this->tenant))->toBeTrue();

    Customer::factory()->count(10)->create();

    expect($this->service->canCreateCustomer($this->tenant))->toBeFalse();
});

test('service can check if tenant can create product', function () {
    expect($this->service->canCreateProduct($this->tenant))->toBeTrue();

    Product::factory()->count(20)->create();

    expect($this->service->canCreateProduct($this->tenant))->toBeFalse();
});

test('service can check if tenant can create invoice', function () {
    expect($this->service->canCreateInvoice($this->tenant))->toBeTrue();

    Invoice::factory()->count(50)->create();

    expect($this->service->canCreateInvoice($this->tenant))->toBeFalse();
});

test('service can check if tenant has reached limit', function () {
    expect($this->service->hasReachedLimit($this->tenant, 'users'))->toBeFalse();

    User::factory()->count(2)->create();

    expect($this->service->hasReachedLimit($this->tenant, 'users'))->toBeTrue();
});

test('service can get current usage', function () {
    User::factory()->count(2)->create();

    expect($this->service->getCurrentUsage('users'))->toBe(2);
});

test('service can get limit for feature', function () {
    expect($this->service->getLimit($this->tenant, 'users'))->toBe(2);
});

test('service returns null for unlimited features', function () {
    $this->tenant->plan_id = $this->enterprisePlan->id;
    $this->tenant->save();

    expect($this->service->getLimit($this->tenant, 'users'))->toBeNull();
});

test('service can get remaining capacity', function () {
    User::factory()->count(1)->create();

    expect($this->service->getRemainingCapacity($this->tenant, 'users'))->toBe(1);
});

test('service returns null for remaining capacity when unlimited', function () {
    $this->tenant->plan_id = $this->enterprisePlan->id;
    $this->tenant->save();

    expect($this->service->getRemainingCapacity($this->tenant, 'users'))->toBeNull();
});

test('service can get usage percentage', function () {
    User::factory()->count(1)->create();

    $percentage = $this->service->getUsagePercentage($this->tenant, 'users');

    expect($percentage)->toBe(50.0);
});

test('service returns null for usage percentage when unlimited', function () {
    $this->tenant->plan_id = $this->enterprisePlan->id;
    $this->tenant->save();

    expect($this->service->getUsagePercentage($this->tenant, 'users'))->toBeNull();
});

test('service can get usage statistics', function () {
    User::factory()->count(1)->create();
    Customer::factory()->count(5)->create();

    $statistics = $this->service->getUsageStatistics($this->tenant);

    expect($statistics)->toHaveKey('users');
    expect($statistics['users']['current'])->toBe(1);
    expect($statistics['users']['limit'])->toBe(2);
    expect($statistics['users']['remaining'])->toBe(1);
    expect($statistics['users']['percentage'])->toBe(50.0);
});

test('service can check if tenant can access feature', function () {
    expect($this->service->canAccessFeature($this->tenant, 'Basic support'))->toBeTrue();
    expect($this->service->canAccessFeature($this->tenant, 'API access'))->toBeFalse();
});

test('service can get available features', function () {
    $features = $this->service->getAvailableFeatures($this->tenant);

    expect($features)->toBeArray();
    expect($features)->toContain('Basic support');
});

test('service can enforce limit', function () {
    User::factory()->count(2)->create();

    expect(fn() => $this->service->enforceLimit($this->tenant, 'users'))
        ->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

test('service does not throw when limit not reached', function () {
    User::factory()->count(1)->create();

    $this->service->enforceLimit($this->tenant, 'users');

    expect(true)->toBeTrue();
});

test('service can check if tenant needs upgrade', function () {
    expect($this->service->needsUpgrade($this->tenant))->toBeFalse();

    User::factory()->count(2)->create();

    expect($this->service->needsUpgrade($this->tenant))->toBeTrue();
});

test('service can get recommended plan for upgrade', function () {
    $this->tenant->plan_id = $this->freePlan->id;
    $this->tenant->save();

    $recommended = $this->service->getRecommendedPlan($this->tenant);

    expect($recommended->slug)->toBe('pro');
});

test('service returns null for recommended plan when on enterprise', function () {
    $this->tenant->plan_id = $this->enterprisePlan->id;
    $this->tenant->save();

    $recommended = $this->service->getRecommendedPlan($this->tenant);

    expect($recommended)->toBeNull();
});

test('service returns pro plan when tenant has no plan', function () {
    $this->tenant->plan_id = null;
    $this->tenant->save();

    $recommended = $this->service->getRecommendedPlan($this->tenant);

    expect($recommended->slug)->toBe('pro');
});