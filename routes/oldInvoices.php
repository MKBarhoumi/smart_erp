<?php
use App\Http\Controllers\OldInvoiceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
   // OldInvoices
    Route::resource('oldinvoices', OldInvoiceController::class);
    Route::prefix('oldinvoices/{oldinvoice}')->name('oldinvoices.')->group(function () {
        Route::post('/validate', [OldInvoiceController::class, 'validateOldInvoice'])->name('validate');
        Route::post('/sign', [OldInvoiceController::class, 'sign'])->name('sign');
        Route::post('/submit', [OldInvoiceController::class, 'submit'])->name('submit');
        Route::get('/pdf', [OldInvoiceController::class, 'downloadPdf'])->name('pdf');
        Route::get('/xml', [OldInvoiceController::class, 'downloadXml'])->name('xml');
        Route::post('/duplicate', [OldInvoiceController::class, 'duplicate'])->name('duplicate');
    });