<?php

namespace Modules\Sales\Listeners;

use Modules\Payments\Events\PaymentIntentSucceeded;
use Modules\Sales\Services\CheckoutService;

// Deliberately does NOT implement ShouldQueue — see PaymentIntentSucceeded's
// docblock. This must complete within the webhook request.
class ConfirmOrderPayment
{
    public function __construct(
        private readonly CheckoutService $checkout,
    ) {}

    public function handle(PaymentIntentSucceeded $event): void
    {
        $this->checkout->confirmPayment($event->providerReference);
    }
}
