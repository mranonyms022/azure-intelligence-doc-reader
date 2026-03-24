<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\InvoiceList;
use App\Livewire\InvoiceDetail;
use App\Livewire\Admin\UserManager;

// ── Guest routes ──────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/',      Login::class)->name('login');
    Route::get('/login', Login::class);
});

// ── Authenticated routes ──────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/dashboard',           Dashboard::class)->name('dashboard');
    Route::get('/invoices',            InvoiceList::class)->name('invoices.index');
    Route::get('/invoices/{id}',       InvoiceDetail::class)->name('invoices.show');

    // Admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users', UserManager::class)->name('admin.users');
    });

    // Logout
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');
});
