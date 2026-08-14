<?php

use Illuminate\Support\Facades\Route;
use Modules\Shipping\Http\Controllers\Api\Merchant\ShippingRateController as MerchantShippingRateController;
use Modules\Shipping\Http\Controllers\Api\ShippingRateController;

Route::get('store/shipping-rates', [ShippingRateController::class, 'index'])->name('store.shipping-rates.index');

Route::middleware('auth:sanctum')->prefix('merchant/shipping-rates')->group(function () {
    Route::get('/', [MerchantShippingRateController::class, 'index'])->name('merchant.shipping-rates.index');
    Route::post('/', [MerchantShippingRateController::class, 'store'])->name('merchant.shipping-rates.store');
    Route::put('{rateId}', [MerchantShippingRateController::class, 'update'])->name('merchant.shipping-rates.update');
    Route::delete('{rateId}', [MerchantShippingRateController::class, 'destroy'])->name('merchant.shipping-rates.destroy');
});