<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    Artisan::call('migrate:fresh');
    Artisan::call('db:seed', ['--class' => 'PlansSeeder']);

    $this->freePlan = Plan::where('slug', 'free')->first();
    $this->proPlan = Plan::where('slug', 'pro')->first();
    $this->enterprisePlan = Plan::where('slug', 'enterprise')->first();
});

afterEach(function () {
    // Cleanup
});

test('plans are seeded correctly', function () {
    expect(Plan::count())->toBe(3);
    expect(Plan::where('slug', 'free')->exists())->toBeTrue();
    expect(Plan::where('slug', 'pro')->exists())->toBeTrue();
    expect(Plan::where('slug', 'enterprise')->exists())->toBeTrue();
});

test('free plan has correct limits', function () {
    $plan = Plan::where('slug', 'free')->first();

    expect($plan->max_users)->toBe(2);
    expect($plan->max_customers)->toBe(10);
    expect($plan->max_products)->toBe(20);
    expect($plan->max_invoices)->toBe(50);
    expect($plan->isFree())->toBeTrue();
});

test('pro plan has correct limits', function () {
    $plan = Plan::where('slug', 'pro')->first();

    expect($plan->max_users)->toBe(10);
    expect($plan->max_customers)->toBe(100);
    expect($plan->max_products)->toBe(500);
    expect($plan->max_invoices)->toBe(1000);
    expect($plan->isFree())->toBeFalse();
});

test('enterprise plan has unlimited limits', function () {
    $plan = Plan::where('slug', 'enterprise')->first();

    expect($plan->max_users)->toBeNull();
    expect($plan->max_customers)->toBeNull();
    expect($plan->max_products)->toBeNull();
    expect($plan->max_invoices)->toBeNull();
    expect($plan->isFree())->toBeFalse();
});

test('plan has formatted price attribute', function () {
    $plan = Plan::where('slug', 'pro')->first();

    expect($plan->formatted_price)->toBe('29.00 USD');
});

test('plan has interval display attribute', function () {
    $plan = Plan::where('slug', 'pro')->first();

    expect($plan->interval_display)->toBe('Monthly');
});

test('plan can check if it has limit for feature', function () {
    $freePlan = Plan::where('slug', 'free')->first();
    $enterprisePlan = Plan::where('slug', 'enterprise')->first();

    expect($freePlan->hasLimit('users'))->toBeTrue();
    expect($enterprisePlan->hasLimit('users'))->toBeFalse();
});

test('plan can get limit for feature', function () {
    $freePlan = Plan::where('slug', 'free')->first();
    $enterprisePlan = Plan::where('slug', 'enterprise')->first();

    expect($freePlan->getLimit('users'))->toBe(2);
    expect($enterprisePlan->getLimit('users'))->toBeNull();
});

test('active scope returns only active plans', function () {
    $activePlans = Plan::active()->get();

    expect($activePlans->count())->toBe(3);

    Plan::where('slug', 'pro')->update(['is_active' => false]);

    $activePlans = Plan::active()->get();

    expect($activePlans->count())->toBe(2);
});

test('ordered scope returns plans in correct order', function () {
    $plans = Plan::ordered()->get();

    expect($plans->first()->slug)->toBe('free');
    expect($plans->last()->slug)->toBe('enterprise');
});

test('plan features are stored as array', function () {
    $plan = Plan::where('slug', 'pro')->first();

    expect($plan->features)->toBeArray();
    expect($plan->features)->toContain('API access');
});

test('tenant can be assigned to plan', function () {
    $tenant = Tenant::create([
        'id' => 'test-tenant',
        'name' => 'Test Tenant',
    ]);

    $plan = Plan::where('slug', 'pro')->first();

    $tenant->plan_id = $plan->id;
    $tenant->save();

    expect($tenant->plan->slug)->toBe('pro');
});

test('tenant can check if on specific plan', function () {
    $tenant = Tenant::create([
        'id' => 'test-tenant',
        'name' => 'Test Tenant',
        'plan_id' => $this->proPlan->id,
    ]);

    expect($tenant->isOnPlan('pro'))->toBeTrue();
    expect($tenant->isOnPlan('free'))->toBeFalse();
});

test('tenant can upgrade plan', function () {
    $tenant = Tenant::create([
        'id' => 'test-tenant',
        'name' => 'Test Tenant',
        'plan_id' => $this->freePlan->id,
    ]);

    $result = $tenant->upgradePlan($this->proPlan);

    expect($result)->toBeTrue();
    expect($tenant->plan->slug)->toBe('pro');
});

test('tenant upgrade to same plan returns false', function () {
    $tenant = Tenant::create([
        'id' => 'test-tenant',
        'name' => 'Test Tenant',
        'plan_id' => $this->proPlan->id,
    ]);

    $result = $tenant->upgradePlan($this->proPlan);

    expect($result)->toBeFalse();
});

test('tenant can get current plan', function () {
    $tenant = Tenant::create([
        'id' => 'test-tenant',
        'name' => 'Test Tenant',
        'plan_id' => $this->proPlan->id,
    ]);

    $currentPlan = $tenant->getCurrentPlan();

    expect($currentPlan->slug)->toBe('pro');
});

test('tenant without plan returns null for current plan', function () {
    $tenant = Tenant::create([
        'id' => 'test-tenant',
        'name' => 'Test Tenant',
    ]);

    $currentPlan = $tenant->getCurrentPlan();

    expect($currentPlan)->toBeNull();
});