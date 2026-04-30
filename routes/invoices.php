<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Invoice CRUD
    Route::resource('invoices', InvoiceController::class);

    // Invoice import from XML
    Route::post('/invoices-import/parse', [InvoiceController::class, 'parseXml'])->name('invoices.parseXml');
    Route::post('/invoices-import/store', [InvoiceController::class, 'importXml'])->name('invoices.importXml');

    // Invoice actions
    Route::prefix('invoices/{invoice}')->name('invoices.')->group(function () {
        Route::post('/request-validation', [InvoiceController::class, 'requestValidation'])->name('requestValidation');
        Route::post('/validate', [InvoiceController::class, 'validateInvoice'])->name('validate');
        Route::post('/reject-validation', [InvoiceController::class, 'rejectValidation'])->name('rejectValidation');
        Route::post('/sign', [InvoiceController::class, 'sign'])->name('sign');
        Route::post('/submit', [InvoiceController::class, 'submit'])->name('submit');
        Route::get('/xml', [InvoiceController::class, 'downloadXml'])->name('xml');
        Route::get('/xml-without-tva', [InvoiceController::class, 'downloadXmlWithoutTva'])->name('xmlWithoutTva');
        Route::get('/pdf', [InvoiceController::class, 'downloadPdf'])->name('pdf');
        Route::get('/pdf-without-tva', [InvoiceController::class, 'downloadPdfWithoutTva'])->name('pdfWithoutTva');
        Route::post('/duplicate', [InvoiceController::class, 'duplicate'])->name('duplicate');
        
        // Invoice payments
        Route::post('/payments', [InvoiceController::class, 'storePayment'])->name('payments.store');
        Route::delete('/payments/{payment}', [InvoiceController::class, 'destroyPayment'])->name('payments.destroy');
    });
});
