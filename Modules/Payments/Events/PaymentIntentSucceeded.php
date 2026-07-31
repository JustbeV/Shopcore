<?php

namespace Modules\Payments\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired directly from the webhook controller — deliberately NOT a queued
 * job at this layer. The order-confirmation side effect (marking the order
 * paid + committing inventory) must happen synchronously within the webhook
 * request per §12 ("DecrementInventory: sync, within DB transaction"), so
 * this event's listener (Modules\Sales\Listeners\ConfirmOrderPayment) does
 * NOT implement ShouldQueue. Anything that genuinely can wait (confirmation
 * emails, analytics) is deferred further downstream via the OrderPlaced
 * event, which IS queued.
 */
class PaymentIntentSucceeded
{
    use Dispatchable;

    public function __construct(
        public readonly string $providerReference,
        public readonly string $provider = 'stripe',
    ) {}
}