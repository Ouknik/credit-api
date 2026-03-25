<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\GatewayCallbackController;
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

// Gateway callback (Pi pushes recharge results — authenticated by gateway token)
Route::post('gateway/callback', GatewayCallbackController::class);

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('otp/send', [AuthController::class, 'sendOtp']);
    Route::post('otp/verify', [AuthController::class, 'verifyOtp']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    // Keep refresh outside jwt.auth so expired (but refreshable) tokens can renew session.
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Protected routes
Route::middleware(['jwt.auth'])->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
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
    Route::get('customers/{customer}/stats', [CustomerController::class, 'stats']);
    
    // Customer Debts & Payments
    Route::get('customers/{customer}/debts', [DebtController::class, 'customerDebts']);
    Route::post('customers/{customer}/debts', [DebtController::class, 'addDebt']);
    Route::post('customers/{customer}/payments', [DebtController::class, 'registerPayment']);

    // All Debts
    Route::get('debts', [DebtController::class, 'index']);

    // Recharges
    Route::get('recharges', [RechargeController::class, 'index']);
    Route::post('recharges', [RechargeController::class, 'store'])->middleware('recharge.limit');
    Route::post('recharges/{id}/cancel', [RechargeController::class, 'cancel']);
    Route::get('recharges/operators', [RechargeController::class, 'operators']);
    Route::get('recharges/gateway-health', [RechargeController::class, 'gatewayHealth']);
    Route::get('recharges/{id}', [RechargeController::class, 'show']);

    // Wallet Transactions
    Route::get('wallet/transactions', [WalletApiController::class, 'index']);
});
