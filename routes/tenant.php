<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Api\CompanySettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes (With Tenancy)
|--------------------------------------------------------------------------
|
| These routes run in the tenant database context.
| They are used for the actual application features.
|
*/

// All tenant routes require tenancy initialization
Route::middleware(['tenant'])->group(function () {

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
        Route::get('/app', [DashboardController::class, 'index'])->name('app.dashboard');

        // Customers
        Route::resource('customers', CustomerController::class);

        // Products
        Route::resource('products', ProductController::class);

        // Services
        Route::resource('services', ServiceController::class);

        // Invoices
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [\App\Http\Controllers\InvoiceController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\InvoiceController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\InvoiceController::class, 'store'])->name('store');
            Route::get('/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'show'])->name('show');
            Route::get('/{invoice}/edit', [\App\Http\Controllers\InvoiceController::class, 'edit'])->name('edit');
            Route::put('/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'update'])->name('update');
            Route::delete('/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'destroy'])->name('destroy');
        });

        // Old Invoices
        Route::prefix('old-invoices')->name('old-invoices.')->group(function () {
            Route::get('/', [\App\Http\Controllers\OldInvoiceController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\OldInvoiceController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\OldInvoiceController::class, 'store'])->name('store');
            Route::get('/{oldInvoice}', [\App\Http\Controllers\OldInvoiceController::class, 'show'])->name('show');
            Route::get('/{oldInvoice}/edit', [\App\Http\Controllers\OldInvoiceController::class, 'edit'])->name('edit');
            Route::put('/{oldInvoice}', [\App\Http\Controllers\OldInvoiceController::class, 'update'])->name('update');
            Route::delete('/{oldInvoice}', [\App\Http\Controllers\OldInvoiceController::class, 'destroy'])->name('destroy');
        });

        // Payments
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::get('/create', [PaymentController::class, 'create'])->name('create');
            Route::post('/', [PaymentController::class, 'store'])->name('store');
            Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
            Route::get('/{payment}/edit', [PaymentController::class, 'edit'])->name('edit');
            Route::put('/{payment}', [PaymentController::class, 'update'])->name('update');
            Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('destroy');
        });

        // Inventory
        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', [InventoryController::class, 'index'])->name('index');
            Route::get('/movements', [InventoryController::class, 'movements'])->name('movements');
            Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust');
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
            Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
        });

        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::post('/company', [SettingsController::class, 'updateCompany'])->name('company');
            Route::post('/company-settings/certificate', [SettingsController::class, 'uploadCertificate'])->name('company-settings.certificate');
            Route::post('/company-settings/logo', [SettingsController::class, 'uploadLogo'])->name('company-settings.logo');
        });

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        });

        // Admin routes (admin only)
        Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
            Route::resource('users', UserController::class)->except(['show']);
            Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
            Route::get('/profiles', [ProfileController::class, 'index'])->name('profiles.index');
            Route::post('/profiles', [ProfileController::class, 'store'])->name('profiles.store');
            Route::put('/profiles/{role}', [ProfileController::class, 'update'])->name('profiles.update');
            Route::delete('/profiles/{role}', [ProfileController::class, 'destroy'])->name('profiles.destroy');
        });
    });

    // Tenant test route
    Route::get('/test-tenancy', function () {
        $tenancy = tenancy();
        return [
            'tenant' => $tenancy->tenant ? [
                'id' => $tenancy->tenant->id,
                'name' => $tenancy->tenant->name,
                'database' => $tenancy->tenant->database_name,
            ] : null,
            'initialized' => $tenancy->initialized,
            'db_connection' => \Illuminate\Support\Facades\DB::getDefaultConnection(),
            'db_name' => \Illuminate\Support\Facades\DB::connection()->getDatabaseName(),
        ];
    })->name('test.tenancy');
});