<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SoapController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// SOAP endpoints (no auth, no CSRF)
Route::get('/soap/invoicing', [SoapController::class, 'wsdl'])->name('soap.wsdl');
Route::post('/soap/invoicing', [SoapController::class, 'handle'])->name('soap.handle');

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

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

    // Dashboard (alternate route)
    Route::get('/app', [DashboardController::class, 'index'])->name('app.dashboard');

    // Customers
    Route::resource('customers', CustomerController::class);

    // Products
    Route::resource('products', ProductController::class);

    // Services
    Route::resource('services', ServiceController::class);

    // Payments
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/oldinvoices/{oldinvoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/', [NotificationController::class, 'clearAll'])->name('clear-all');
    });

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/history', [InventoryController::class, 'history'])->name('inventory.history');
    Route::post('/inventory/adjustment', [InventoryController::class, 'adjustment'])->name('inventory.adjustment');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
        Route::get('/tax-summary', [ReportController::class, 'taxSummary'])->name('tax-summary');
        Route::get('/customer-aging', [ReportController::class, 'customerAging'])->name('customer-aging');
        Route::get('/timbre', [ReportController::class, 'timbre'])->name('timbre');
        Route::get('/customer-statement', [ReportController::class, 'customerStatementSelect'])->name('customer-statement');
        Route::get('/customer-statement/{customer}', [ReportController::class, 'customerStatement'])->name('customer-statement.show');

        // PDF Downloads
        Route::get('/revenue/pdf', [ReportController::class, 'revenuePdf'])->name('revenue.pdf');
        Route::get('/tax-summary/pdf', [ReportController::class, 'taxSummaryPdf'])->name('tax-summary.pdf');
        Route::get('/customer-aging/pdf', [ReportController::class, 'customerAgingPdf'])->name('customer-aging.pdf');
        Route::get('/timbre/pdf', [ReportController::class, 'timbrePdf'])->name('timbre.pdf');
        Route::get('/customer-statement/{customer}/pdf', [ReportController::class, 'customerStatementPdf'])->name('customer-statement.pdf');
    });

    // Company Settings (admin only)
    Route::middleware('can:manage-settings')->group(function () {
        Route::get('/company-settings', [SettingsController::class, 'edit'])->name('company-settings.edit');
        Route::put('/company-settings', [SettingsController::class, 'update'])->name('company-settings.update');
        Route::post('/company-settings/certificate', [SettingsController::class, 'uploadCertificate'])->name('company-settings.certificate');
        Route::post('/company-settings/logo', [SettingsController::class, 'uploadLogo'])->name('company-settings.logo');
    });

    // Admin (admin only)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
        Route::get('/profiles', [ProfileController::class, 'index'])->name('profiles.index');
        Route::post('/profiles', [ProfileController::class, 'store'])->name('profiles.store');
        Route::put('/profiles/{role}', [ProfileController::class, 'update'])->name('profiles.update');
        Route::delete('/profiles/{role}', [ProfileController::class, 'destroy'])->name('profiles.destroy');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/oldInvoices.php';
require __DIR__.'/invoices.php';

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
