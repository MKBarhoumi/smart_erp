<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Foundation\Application;

/*
|--------------------------------------------------------------------------
| Central Routes (No Tenancy)
|--------------------------------------------------------------------------
|
| These routes run in the central database context.
| They are used for landing pages, signup, login, etc.
|
*/

// SOAP endpoints (no auth, no CSRF)
Route::get('/soap/invoicing', [\App\Http\Controllers\SoapController::class, 'wsdl'])->name('soap.wsdl');
Route::post('/soap/invoicing', [\App\Http\Controllers\SoapController::class, 'handle'])->name('soap.handle');

// Public health check endpoint (no auth, no CSRF)
Route::get('/health', function () {
    $healthCheck = new \App\Services\Monitoring\HealthCheckService();
    $result = $healthCheck->check();
    
    $statusCode = $result['status'] === 'healthy' ? 200 : 503;
    
    return response()->json($result, $statusCode);
})->name('health.check');

// Stripe webhook (no auth, no CSRF)
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');

// Tenant registration (central context only) - CRITICAL RATE LIMIT
Route::middleware(['throttle:critical'])->group(function () {
    Route::post('/api/register-tenant', [\App\Http\Controllers\TenantRegistrationController::class, 'store'])->name('tenant.register');
});

// Billing routes (central context only, authenticated) - BILLING RATE LIMIT
Route::middleware(['auth', 'throttle:billing'])->group(function () {
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BillingController::class, 'index'])->name('index');
        Route::post('/checkout', [\App\Http\Controllers\BillingController::class, 'checkout'])->name('checkout');
        Route::get('/success', [\App\Http\Controllers\BillingController::class, 'success'])->name('success');
        Route::get('/cancel', [\App\Http\Controllers\BillingController::class, 'cancel'])->name('cancel');
        Route::post('/portal', [\App\Http\Controllers\BillingController::class, 'portal'])->name('portal');
        Route::post('/cancel-subscription', [\App\Http\Controllers\BillingController::class, 'cancelSubscription'])->name('cancel-subscription');
        Route::post('/resume-subscription', [\App\Http\Controllers\BillingController::class, 'resumeSubscription'])->name('resume-subscription');
        Route::post('/swap-plan', [\App\Http\Controllers\BillingController::class, 'swapPlan'])->name('swap-plan');
        Route::get('/subscription', [\App\Http\Controllers\BillingController::class, 'subscription'])->name('subscription');
        Route::get('/invoices', [\App\Http\Controllers\BillingController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoiceId}/download', [\App\Http\Controllers\BillingController::class, 'downloadInvoice'])->name('download-invoice');

        // Payment methods
        Route::post('/payment-methods', [\App\Http\Controllers\BillingController::class, 'addPaymentMethod'])->name('add-payment-method');
        Route::put('/payment-methods/default', [\App\Http\Controllers\BillingController::class, 'updateDefaultPaymentMethod'])->name('update-default-payment-method');
        Route::get('/payment-methods', [\App\Http\Controllers\BillingController::class, 'paymentMethods'])->name('payment-methods');
        Route::delete('/payment-methods/{paymentMethodId}', [\App\Http\Controllers\BillingController::class, 'deletePaymentMethod'])->name('delete-payment-method');
    });
});

// Monitoring routes (central context only, authenticated)
Route::middleware(['auth'])->group(function () {
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/health', [\App\Http\Controllers\MonitoringController::class, 'health'])->name('health');
        Route::get('/webhooks', [\App\Http\Controllers\MonitoringController::class, 'webhooks'])->name('webhooks');
        Route::get('/billing-metrics', [\App\Http\Controllers\MonitoringController::class, 'billingMetrics'])->name('billing-metrics');
        Route::get('/tenant-usage', [\App\Http\Controllers\MonitoringController::class, 'tenantUsage'])->name('tenant-usage');
        Route::get('/dlq-report', [\App\Http\Controllers\MonitoringController::class, 'dlqReport'])->name('dlq-report');
        Route::get('/dashboard', [\App\Http\Controllers\MonitoringController::class, 'dashboard'])->name('dashboard');

        // Actions
        Route::post('/retry-failed-webhooks', [\App\Http\Controllers\MonitoringController::class, 'retryFailedWebhooks'])->name('retry-failed-webhooks');
        Route::post('/retry-stuck-webhooks', [\App\Http\Controllers\MonitoringController::class, 'retryStuckWebhooks'])->name('retry-stuck-webhooks');
        Route::post('/sync-tenant-usage', [\App\Http\Controllers\MonitoringController::class, 'syncTenantUsage'])->name('sync-tenant-usage');
        Route::post('/cleanup-webhooks', [\App\Http\Controllers\MonitoringController::class, 'cleanupWebhooks'])->name('cleanup-webhooks');
        Route::post('/trigger-health-check', [\App\Http\Controllers\MonitoringController::class, 'triggerHealthCheck'])->name('trigger-health-check');
    });
});

// Guest routes (landing, login, register)
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    })->name('home');
});

// Error page test routes (only in development)
if (app()->environment('local')) {
    Route::prefix('test-errors')->group(function () {
        Route::get('/403', fn() => Inertia::render('Errors/Error403'))->name('test.error403');
        Route::get('/404', fn() => Inertia::render('Errors/Error404'))->name('test.error404');
        Route::get('/419', fn() => Inertia::render('Errors/Error419'))->name('test.error419');
        Route::get('/500', fn() => Inertia::render('Errors/Error500'))->name('test.error500');
        Route::get('/503', fn() => Inertia::render('Errors/Error503'))->name('test.error503');
    });
}