<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\Api\AuthController;
use Modules\CRM\Http\Controllers\Api\PasswordResetController;
use Modules\CRM\Http\Controllers\Api\ProfileController;

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
