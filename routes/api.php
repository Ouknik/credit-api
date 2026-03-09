<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\RechargeController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\WalletApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Credit & Recharge Platform API Routes
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware(['jwt.auth'])->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Dashboard & Reports
    Route::get('dashboard', [ReportController::class, 'dashboard']);
    Route::prefix('reports')->group(function () {
        Route::get('daily', [ReportController::class, 'daily']);
        Route::get('monthly', [ReportController::class, 'monthly']);
    });

    // Customers
    Route::apiResource('customers', CustomerController::class);
    
    // Customer Debts & Payments
    Route::get('customers/{customer}/debts', [DebtController::class, 'customerDebts']);
    Route::post('customers/{customer}/debts', [DebtController::class, 'addDebt']);
    Route::post('customers/{customer}/payments', [DebtController::class, 'registerPayment']);

    // All Debts
    Route::get('debts', [DebtController::class, 'index']);

    // Recharges
    Route::get('recharges', [RechargeController::class, 'index']);
    Route::post('recharges', [RechargeController::class, 'store'])->middleware('recharge.limit');
    Route::get('recharges/operators', [RechargeController::class, 'operators']);

    // Wallet Transactions
    Route::get('wallet/transactions', [WalletApiController::class, 'index']);
});
