<?php

namespace Modules\Payments\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Payments\Events\PaymentIntentFailed;
use Modules\Payments\Events\PaymentIntentSucceeded;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                config('services.stripe.webhook_secret'),
            );
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);

            return response('Invalid signature', 400);
        }

        match ($event->type) {
            'payment_intent.succeeded' => PaymentIntentSucceeded::dispatch(
                $event->data->object->id,
            ),
            'payment_intent.payment_failed' => PaymentIntentFailed::dispatch(
                $event->data->object->id,
                'stripe',
                $event->data->object->last_payment_error?->message,
            ),
            // Other event types (charge.refunded, etc.) will be added
            // alongside the Refund flow in the next pass — intentionally
            // ignored (200'd, not errored) for now so Stripe doesn't retry
            // them forever.
            default => null,
        };

        // Always 200 quickly once the event is dispatched — Stripe retries
        // on non-2xx, and our synchronous listener already does the
        // meaningful work before this returns.
        return response('', 200);
    }
}