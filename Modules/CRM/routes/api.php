<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\Api\AuthController;
use Modules\CRM\Http\Controllers\Api\Merchant\ReviewController as MerchantReviewController;
use Modules\CRM\Http\Controllers\Api\PasswordResetController;
use Modules\CRM\Http\Controllers\Api\ProfileController;
use Modules\CRM\Http\Controllers\Api\Storefront\ReviewController;
use Modules\CRM\Http\Controllers\Api\Storefront\WishlistController;

/*
|--------------------------------------------------------------------------
| CRM Module API Routes
|--------------------------------------------------------------------------
| Mounted under /api/v1/store by the main routes/api.php. Auth endpoints
| use a `customer-auth` named limiter (register it in RouteServiceProvider
| alongside `auth`/`checkout`/`cart-write` — 5/min by email+ip, matching
| §6.2's brute-force protection for the merchant login).
*/

Route::prefix('store/auth')->middleware('throttle:customer-auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('store.auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('store.auth.login');
    Route::post('forgot-password', [PasswordResetController::class, 'forgot'])->name('store.auth.forgot-password');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('store.auth.reset-password');
});

Route::post('store/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth:customer')
    ->name('store.auth.logout');

Route::middleware('auth:customer')->prefix('store/account/profile')->group(function () {
    Route::get('/', [ProfileController::class, 'show'])->name('store.account.profile.show');
    Route::put('/', [ProfileController::class, 'update'])->name('store.account.profile.update');
    Route::put('password', [ProfileController::class, 'updatePassword'])->name('store.account.profile.password');
});

// --- Storefront: Reviews (public read, customer-only write) ---
Route::get('store/products/{productId}/reviews', [ReviewController::class, 'index'])
    ->name('store.products.reviews.index');
Route::post('store/products/{productId}/reviews', [ReviewController::class, 'store'])
    ->middleware('auth:customer')
    ->name('store.products.reviews.store');

// --- Storefront: Wishlist (customer-only) ---
Route::middleware('auth:customer')->prefix('store/wishlist')->group(function () {
    Route::get('/', [WishlistController::class, 'index'])->name('store.wishlist.index');
    Route::post('{productId}', [WishlistController::class, 'store'])->name('store.wishlist.store');
    Route::delete('{productId}', [WishlistController::class, 'destroy'])->name('store.wishlist.destroy');
});

// --- Merchant: review moderation ---
Route::middleware('auth:sanctum')->prefix('merchant/reviews')->group(function () {
    Route::get('/', [MerchantReviewController::class, 'index'])->name('merchant.reviews.index');
    Route::post('{reviewId}/approve', [MerchantReviewController::class, 'approve'])->name('merchant.reviews.approve');
    Route::post('{reviewId}/reject', [MerchantReviewController::class, 'reject'])->name('merchant.reviews.reject');
});