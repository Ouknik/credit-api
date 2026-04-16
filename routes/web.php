<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GatewayController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\ProductImportController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ShopController;
use App\Http\Controllers\Admin\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// ══════════════════════════════════════════════════
//  Admin Authentication
// ══════════════════════════════════════════════════
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// ══════════════════════════════════════════════════
//  Admin Protected Routes
// ══════════════════════════════════════════════════
Route::prefix('admin')->middleware(['auth:web', 'admin'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Shops
    Route::get('/shops', [ShopController::class, 'index'])->name('admin.shops.index');
    Route::get('/shops/{shop}', [ShopController::class, 'show'])->name('admin.shops.show');
    Route::patch('/shops/{shop}/toggle-status', [ShopController::class, 'toggleStatus'])->name('admin.shops.toggle-status');

    // Customers
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('admin.customers.index');
    Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->name('admin.customers.show');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports.index');

    // Wallet
    Route::get('/wallet/summary', [WalletController::class, 'summary'])->name('admin.wallet.summary');
    Route::get('/shops/{shop}/wallet', [WalletController::class, 'shopHistory'])->name('admin.shops.wallet.history');
    Route::get('/shops/{shop}/deposit', [WalletController::class, 'depositForm'])->name('admin.shops.wallet.deposit');
    Route::post('/shops/{shop}/deposit', [WalletController::class, 'deposit'])->name('admin.shops.wallet.deposit.submit');

    // Gateway Monitor
    Route::get('/gateway', [GatewayController::class, 'index'])->name('admin.gateway.index');
    Route::get('/gateway/health', [GatewayController::class, 'health'])->name('admin.gateway.health');
    Route::post('/gateway/orange-topup', [GatewayController::class, 'orangeTopup'])->name('admin.gateway.orange-topup');

    // Product management
    Route::get('/products', [ProductAdminController::class, 'index'])->name('admin.products.index');
    Route::post('/products', [ProductAdminController::class, 'store'])->name('admin.products.store');
    Route::get('/products/{product}/edit', [ProductAdminController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{product}', [ProductAdminController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{product}', [ProductAdminController::class, 'destroy'])->name('admin.products.destroy');

    // Products CSV Import (no price)
    Route::get('/products/import', [ProductImportController::class, 'showImportForm'])->name('admin.products.import.form');
    Route::post('/products/import', [ProductImportController::class, 'import'])->name('admin.products.import.submit');
    Route::get('/products/import/template', [ProductImportController::class, 'downloadTemplate'])->name('admin.products.import.template');
});
