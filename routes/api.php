<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvoiceController;

// Invoice DMS REST API
Route::prefix('invoices')->group(function () {
    Route::get('/',          [InvoiceController::class, 'index']);
    Route::get('/stats',     [InvoiceController::class, 'stats']);
    Route::get('/{id}',      [InvoiceController::class, 'show']);
});

Route::prefix('stores')->group(function () {
    Route::get('/',                          [InvoiceController::class, 'stores']);
    Route::get('/{code}/invoices',           [InvoiceController::class, 'storeInvoices']);
});
