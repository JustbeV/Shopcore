<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\Api\StripeWebhookController;

/*
|--------------------------------------------------------------------------
| Payments Module Routes
|--------------------------------------------------------------------------
| IMPORTANT: this route must be registered OUTSIDE the IdentifyTenant
| middleware group. Stripe calls a single fixed URL for the whole platform
| (we're on one platform-level Stripe account, not Stripe Connect per
| store), so there's no Host header to resolve a tenant from. The tenant is
| instead recovered inside ConfirmOrderPayment/ReleaseOrderReservation from
| the Payment's own store_id, via TenantContext::run().
|
| Also exempt from CSRF (already true for API routes) and from the `api`
| rate limiter — Stripe's retry behavior on 429s is worse than just letting
| it through; the signature check is the real gate here.
*/

Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('webhooks.stripe')
    ->withoutMiddleware(['throttle:api']);