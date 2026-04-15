<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\GatewayCallbackController;
use App\Http\Controllers\Api\RechargeController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\WalletApiController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\Distributor\OfferController as V1DistributorOfferController;
use App\Http\Controllers\Api\V1\Distributor\OrderController as V1DistributorOrderController;
use App\Http\Controllers\Api\V1\Shop\OrderController as V1ShopOrderController;

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

// ──────────────────────────────────────────────────────────────────────────
// Mol7anout v1 API
// ──────────────────────────────────────────────────────────────────────────

Route::prefix('v1')->group(function () {
    // Public catalog endpoints
    Route::get('products', [CatalogController::class, 'products']);
    Route::get('products/{id}', [CatalogController::class, 'product']);
    Route::get('categories', [CatalogController::class, 'categories']);

    // Authenticated workflows
    Route::middleware(['jwt.auth'])->group(function () {
        Route::middleware('shop.role:shop_owner')->group(function () {
            // Shop owner: orders
            Route::get('shop/orders', [V1ShopOrderController::class, 'index']);
            Route::post('shop/orders', [V1ShopOrderController::class, 'store']);
            Route::get('shop/orders/{id}', [V1ShopOrderController::class, 'show']);
            Route::post('shop/orders/{id}/publish', [V1ShopOrderController::class, 'publish']);
            Route::get('shop/orders/{id}/offers', [V1ShopOrderController::class, 'offers']);
            Route::post('shop/orders/{id}/offers/{offerId}/accept', [V1ShopOrderController::class, 'accept']);
            Route::post('shop/orders/{id}/confirm-delivery', [V1ShopOrderController::class, 'confirmDelivery']);
        });

        Route::middleware('shop.role:distributor')->group(function () {
            // Distributor: discover orders and submit offers
            Route::get('distributor/orders/available', [V1DistributorOrderController::class, 'available']);
            Route::get('distributor/orders/{id}', [V1DistributorOrderController::class, 'show']);
            Route::post('distributor/offers', [V1DistributorOfferController::class, 'store']);
            Route::put('distributor/orders/{id}/status', [V1DistributorOrderController::class, 'updateStatus']);
        });
    });
});

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
