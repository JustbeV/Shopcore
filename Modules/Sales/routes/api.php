<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\Api\CartController;

/*
|--------------------------------------------------------------------------
| Sales Module API Routes
|--------------------------------------------------------------------------
| Mounted under /api/v1/store by the main routes/api.php, resolved to a
| tenant by the Host header (IdentifyTenant middleware) as documented in
| §4.1/§4.2. No `auth:customer` here on purpose — guests can hold a cart.
*/

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
