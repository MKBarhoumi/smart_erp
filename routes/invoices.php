<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Invoice CRUD
    Route::resource('invoices', InvoiceController::class);

    // Invoice actions
    Route::prefix('invoices/{invoice}')->name('invoices.')->group(function () {
        Route::post('/request-validation', [InvoiceController::class, 'requestValidation'])->name('requestValidation');
        Route::post('/validate', [InvoiceController::class, 'validateInvoice'])->name('validate');
        Route::post('/reject-validation', [InvoiceController::class, 'rejectValidation'])->name('rejectValidation');
        Route::post('/sign', [InvoiceController::class, 'sign'])->name('sign');
        Route::post('/submit', [InvoiceController::class, 'submit'])->name('submit');
        Route::get('/xml', [InvoiceController::class, 'downloadXml'])->name('xml');
        Route::get('/pdf', [InvoiceController::class, 'downloadPdf'])->name('pdf');
        Route::post('/duplicate', [InvoiceController::class, 'duplicate'])->name('duplicate');
        
        // Invoice payments
        Route::post('/payments', [InvoiceController::class, 'storePayment'])->name('payments.store');
        Route::delete('/payments/{payment}', [InvoiceController::class, 'destroyPayment'])->name('payments.destroy');
    });
});
