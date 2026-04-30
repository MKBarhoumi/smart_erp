# Phase 3 Quick Reference Guide

## 🚀 Quick Start

### 1. Install Dependencies
```bash
composer require laravel/cashier
php artisan vendor:publish --tag="cashier-migrations"
php artisan migrate
```

### 2. Configure Environment
```env
STRIPE_KEY=pk_test_your_key
STRIPE_SECRET=sk_test_your_secret
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
STRIPE_PRO_PRICE_ID=price_pro_id
STRIPE_ENTERPRISE_PRICE_ID=price_enterprise_id
```

### 3. Seed Plans
```bash
php artisan db:seed --class=PlansSeeder
```

### 4. Test Webhook Locally
```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

## 📋 Key Files

### Billing Core
- `app/Models/Plan.php` - Plan model
- `app/Models/Tenant.php` - Billable tenant model
- `app/Http/Controllers/BillingController.php` - Billing controller
- `app/Http/Controllers/StripeWebhookController.php` - Webhook handler

### Middleware
- `app/Http/Middleware/EnsureTenantIsSubscribed.php` - Subscription check
- `app/Http/Middleware/EnforcePlanLimits.php` - Plan limits

### Services
- `app/Services/PlanLimitsService.php` - Limits enforcement

### Tests
- `tests/Feature/BillingPlansTest.php` - Plans tests
- `tests/Feature/PlanLimitsServiceTest.php` - Limits tests

## 🔧 Common Tasks

### Create Subscription
```php
$checkoutSession = $tenant->newSubscription('default', $plan->stripe_price_id)
    ->checkout([
        'success_url' => route('billing.success'),
        'cancel_url' => route('billing.cancel'),
    ]);
```

### Cancel Subscription
```php
$tenant->subscription('default')->cancel();
```

### Resume Subscription
```php
$tenant->subscription('default')->resume();
```

### Swap Plans
```php
$tenant->subscription('default')->swap($newPlan->stripe_price_id);
```

### Check Plan Limits
```php
$service = new PlanLimitsService();
$canCreate = $service->canCreateUser($tenant);
```

### Enforce Plan Limits
```php
$service->enforceLimit($tenant, 'users');
// Throws 403 if limit reached
```

### Get Usage Statistics
```php
$statistics = $service->getUsageStatistics($tenant);
// Returns array with current, limit, remaining, percentage
```

## 🛡️ Middleware Usage

### Protect Routes with Subscription Check
```php
Route::middleware(['tenant', 'subscribed'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/reports', [ReportController::class, 'index']);
});
```

### Protect Routes with Plan Limits
```php
Route::middleware(['plan.limits:users'])->group(function () {
    Route::post('/users', [UserController::class, 'store']);
});

Route::middleware(['plan.limits:customers'])->group(function () {
    Route::post('/customers', [CustomerController::class, 'store']);
});
```

## 📊 Plan Limits

### Free Plan
- Users: 2
- Customers: 10
- Products: 20
- Invoices: 50/month

### Pro Plan
- Users: 10
- Customers: 100
- Products: 500
- Invoices: 1,000/month

### Enterprise Plan
- Users: Unlimited
- Customers: Unlimited
- Products: Unlimited
- Invoices: Unlimited

## 🧪 Testing Commands

### Run All Tests
```bash
./vendor/bin/pest
```

### Run Billing Tests
```bash
./vendor/bin/pest tests/Feature/BillingPlansTest.php
./vendor/bin/pest tests/Feature/PlanLimitsServiceTest.php
```

### Seed Test Data
```bash
php artisan migrate:fresh
php artisan db:seed --class=PlansSeeder
```

## 🔍 Troubleshooting

### Webhook Issues
```bash
# Check webhook secret
echo $STRIPE_WEBHOOK_SECRET

# Test webhook locally
stripe trigger invoice.payment_succeeded
```

### Subscription Issues
```bash
# Check tenant subscription
php artisan tinker
>>> $tenant = \App\Models\Tenant::first();
>>> $tenant->subscribed('default');
>>> $tenant->subscription('default')->stripe_status;
```

### Plan Limits Issues
```bash
# Check plan limits
php artisan tinker
>>> $tenant = \App\Models\Tenant::first();
>>> $service = new \App\Services\PlanLimitsService();
>>> $service->getUsageStatistics($tenant);
```

## 📡 API Endpoints

### Billing Management
- `GET /billing` - Billing dashboard
- `POST /billing/checkout` - Create checkout session
- `GET /billing/success` - Checkout success
- `GET /billing/cancel` - Checkout cancel
- `POST /billing/portal` - Billing portal
- `POST /billing/cancel-subscription` - Cancel subscription
- `POST /billing/resume-subscription` - Resume subscription
- `POST /billing/swap-plan` - Swap plans
- `GET /billing/subscription` - Get subscription details
- `GET /billing/invoices` - Get invoices
- `GET /billing/invoices/{id}/download` - Download invoice

### Webhook
- `POST /stripe/webhook` - Stripe webhook endpoint

## 🎯 Stripe CLI Commands

### Login
```bash
stripe login
```

### Listen for Webhooks
```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

### Trigger Events
```bash
stripe trigger invoice.payment_succeeded
stripe trigger invoice.payment_failed
stripe trigger customer.subscription.created
stripe trigger customer.subscription.deleted
```

### Create Products
```bash
stripe products create --name="Pro Plan"
stripe prices create --unit-amount=2900 --currency=usd --recurring=interval=month
```

## 🔐 Security Checklist

- ✅ Webhook signature verification enabled
- ✅ Environment variables configured
- ✅ Separate test/production keys
- ✅ Central billing architecture
- ✅ Plan limits enforced
- ✅ Subscription middleware active
- ✅ HTTPS enabled in production

## 📈 Monitoring

### Key Metrics to Track
- Monthly recurring revenue (MRR)
- Customer churn rate
- Trial conversion rate
- Average revenue per user (ARPU)
- Subscription renewal rate

### Logging
```php
// Enable detailed logging
Log::info('Subscription created', ['tenant_id' => $tenant->id]);
Log::warning('Payment failed', ['tenant_id' => $tenant->id]);
Log::error('Webhook error', ['error' => $e->getMessage()]);
```

## 🎨 UI Components (Next Phase)

### Needed Components
- [ ] Billing dashboard
- [ ] Plan comparison cards
- [ ] Checkout form
- [ ] Subscription management
- [ ] Invoice list
- [ ] Payment method management
- [ ] Usage statistics display

## 📞 Support Resources

- **Stripe Docs:** https://stripe.com/docs
- **Laravel Cashier:** https://laravel.com/docs/cashier
- **Phase 3 Summary:** PHASE3_IMPLEMENTATION_SUMMARY.md
- **Environment Setup:** PHASE3_ENVIRONMENT_SETUP.md

---

**Phase 3 Status:** ✅ COMPLETE
**Ready for Testing:** ✅ YES
**Ready for UI:** ✅ YES