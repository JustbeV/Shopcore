<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\app\Http\Controllers\StaffController;

Route::prefix('v1')->group(function (): void {
    // Public/Signed Route for Accepting Invitations
    Route::get('staff/invitations/{staff}/accept', [StaffController::class, 'accept'])
        ->name('api.v1.staff.invitations.accept');

    // Authenticated Staff Management Routes
    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::post('stores/{store}/staff/invite', [StaffController::class, 'invite']);
        Route::delete('staff/{staff}', [StaffController::class, 'revoke']);
    });
});