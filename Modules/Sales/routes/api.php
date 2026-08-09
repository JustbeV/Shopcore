<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\Api\CartController;
use Modules\Sales\Http\Controllers\Api\CheckoutController;
use Modules\Sales\Http\Controllers\Api\Merchant\OrderController as MerchantOrderController;
use Modules\Sales\Http\Controllers\Api\Merchant\RefundController as MerchantRefundController;
use Modules\Sales\Http\Controllers\Api\RefundController;
use Modules\Sales\Http\Controllers\Api\Storefront\AccountOrderController;

/*
|--------------------------------------------------------------------------
| Sales Module API Routes
|--------------------------------------------------------------------------
| Mounted under /api/v1 by the main routes/api.php, resolved to a tenant by
| the Host header (IdentifyTenant middleware) as documented in §4.1/§4.2.
*/

// --- Storefront: Cart (no auth:customer — guests can hold a cart) ---
Route::prefix('store/cart')->group(function () {
    Route::get('/', [CartController::class, 'show'])->name('store.cart.show');
    Route::post('items', [CartController::class, 'storeItem'])
        ->middleware('throttle:cart-write')
        ->name('store.cart.items.store');
    Route::patch('items/{itemId}', [CartController::class, 'updateItem'])
        ->middleware('throttle:cart-write')
        ->name('store.cart.items.update');
    Route::delete('items/{itemId}', [CartController::class, 'destroyItem'])
        ->name('store.cart.items.destroy');
    Route::delete('/', [CartController::class, 'clear'])->name('store.cart.clear');
});

// --- Storefront: Checkout (guest OR customer — CheckoutController resolves
// identity itself, same as CartController) ---
Route::post('store/checkout', [CheckoutController::class, 'initiate'])
    ->middleware('throttle:checkout')
    ->name('store.checkout.initiate');

// --- Storefront: Account order history (authenticated customers only) ---
Route::middleware('auth:customer')->prefix('store/account/orders')->group(function () {
    Route::get('/', [AccountOrderController::class, 'index'])->name('store.account.orders.index');
    Route::get('{orderId}', [AccountOrderController::class, 'show'])->name('store.account.orders.show');
});

// --- Merchant: order management (staff/owner, team-scoped per §5.2) ---
Route::middleware('auth:sanctum')->prefix('merchant/orders')->group(function () {
    Route::get('/', [MerchantOrderController::class, 'index'])->name('merchant.orders.index');
    Route::get('{orderId}', [MerchantOrderController::class, 'show'])->name('merchant.orders.show');
    Route::post('{orderId}/fulfill', [MerchantOrderController::class, 'fulfill'])->name('merchant.orders.fulfill');
});

// --- Storefront: refund requests (authenticated customers only) ---
Route::post('store/orders/{orderId}/refund-request', [RefundController::class, 'store'])
    ->middleware('auth:customer')
    ->name('store.orders.refund-request');

// --- Merchant: refund decisions ---
Route::middleware('auth:sanctum')->prefix('merchant/refunds')->group(function () {
    Route::get('/', [MerchantRefundController::class, 'index'])->name('merchant.refunds.index');
    Route::post('{refundId}/approve', [MerchantRefundController::class, 'approve'])->name('merchant.refunds.approve');
    Route::post('{refundId}/reject', [MerchantRefundController::class, 'reject'])->name('merchant.refunds.reject');
});