<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\Storefront\HomeController;
use Modules\Catalog\Http\Controllers\Storefront\ProductCatalogController;

$appDomain = config('app.central_domain', 'shopcore.test');

// Central Domain Routes (Platform Marketing, Docs, Admin Auth)

Route::domain($appDomain)->group(function () {
    Route::get('/', function () {
        return inertia('Platform/Marketing/Home');
    })->name('platform.home');
});


// Tenant Subdomain Routes ({store}.shopcore.test)
Route::domain('{store}.' . $appDomain)->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('storefront.home');
    Route::get('/products', [ProductCatalogController::class, 'index'])->name('storefront.products.index');
    Route::get('/products/{slug}', [ProductCatalogController::class, 'show'])->name('storefront.products.show');
});