<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Invoice CRUD
    Route::resource('invoices', InvoiceController::class);

    // Invoice actions
    Route::prefix('invoices/{invoice}')->name('invoices.')->group(function () {
        Route::post('/validate', [InvoiceController::class, 'validateInvoice'])->name('validate');
        Route::post('/sign', [InvoiceController::class, 'sign'])->name('sign');
        Route::post('/submit', [InvoiceController::class, 'submit'])->name('submit');
        Route::get('/xml', [InvoiceController::class, 'downloadXml'])->name('xml');
        Route::post('/duplicate', [InvoiceController::class, 'duplicate'])->name('duplicate');
    });
});
