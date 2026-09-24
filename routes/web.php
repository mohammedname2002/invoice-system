<?php

use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

/*
| Every application route requires a signed-in user and an explicit policy
| check (->can()). tests/Feature/RouteAuthorizationTest.php enforces this.
*/

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::controller(CustomerController::class)->prefix('customers')->name('customers.')->group(function () {
        Route::get('/', 'index')->name('index')->can('viewAny', Customer::class);
        Route::get('/create', 'create')->name('create')->can('create', Customer::class);
        Route::post('/', 'store')->name('store')->can('create', Customer::class);
        Route::get('/{customer}', 'show')->name('show')->can('view', 'customer');
        Route::get('/{customer}/edit', 'edit')->name('edit')->can('update', 'customer');
        Route::put('/{customer}', 'update')->name('update')->can('update', 'customer');
        Route::delete('/{customer}', 'destroy')->name('destroy')->can('delete', 'customer');
    });

    Route::controller(ProductController::class)->prefix('products')->name('products.')->group(function () {
        Route::get('/', 'index')->name('index')->can('viewAny', Product::class);
        Route::get('/create', 'create')->name('create')->can('create', Product::class);
        Route::post('/', 'store')->name('store')->can('create', Product::class);
        Route::get('/{product}/edit', 'edit')->name('edit')->can('update', 'product');
        Route::put('/{product}', 'update')->name('update')->can('update', 'product');
        Route::delete('/{product}', 'destroy')->name('destroy')->can('delete', 'product');
    });

    Route::controller(InvoiceController::class)->prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', 'index')->name('index')->can('viewAny', Invoice::class);
        Route::get('/create', 'create')->name('create')->can('create', Invoice::class);
        Route::post('/', 'store')->name('store')->can('create', Invoice::class);
        Route::get('/{invoice}', 'show')->name('show')->can('view', 'invoice');
        Route::get('/{invoice}/pdf', 'pdf')->name('pdf')->can('view', 'invoice');
        Route::get('/{invoice}/edit', 'edit')->name('edit')->can('update', 'invoice');
        Route::put('/{invoice}', 'update')->name('update')->can('update', 'invoice');
        Route::delete('/{invoice}', 'destroy')->name('destroy')->can('delete', 'invoice');
    });

    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])
        ->name('payments.store')->can('recordPayment', 'invoice');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])
        ->name('payments.destroy')->can('delete', 'payment');

    Route::controller(CreditNoteController::class)->prefix('credit-notes')->name('credit-notes.')->group(function () {
        Route::get('/', 'index')->name('index')->can('viewAny', CreditNote::class);
        Route::get('/create', 'create')->name('create')->can('create', CreditNote::class);
        Route::post('/', 'store')->name('store')->can('create', CreditNote::class);
        Route::get('/{creditNote}', 'show')->name('show')->can('view', 'creditNote');
        Route::get('/{creditNote}/pdf', 'pdf')->name('pdf')->can('view', 'creditNote');
        Route::get('/{creditNote}/edit', 'edit')->name('edit')->can('update', 'creditNote');
        Route::put('/{creditNote}', 'update')->name('update')->can('update', 'creditNote');
        Route::delete('/{creditNote}', 'destroy')->name('destroy')->can('delete', 'creditNote');
    });

    Route::controller(ReportController::class)->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', 'index')->name('index')->can('view-reports');
        Route::get('/statement', 'statement')->name('statement')->can('view-reports');
        Route::get('/statement/pdf', 'statementPdf')->name('statement.pdf')->can('view-reports');
    });

    // The signed-in user's own account; the policy is "it is yours".
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
