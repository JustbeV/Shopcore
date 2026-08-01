<?php

namespace Modules\Sales\Listeners;

use Modules\Payments\Events\PaymentIntentFailed;
use Modules\Sales\Services\CheckoutService;

// Also deliberately synchronous, same rationale as ConfirmOrderPayment.
class ReleaseOrderReservation
{
    public function __construct(
        private readonly CheckoutService $checkout,
    ) {}

    public function handle(PaymentIntentFailed $event): void
    {
        $this->checkout->failPayment($event->providerReference, $event->reason);
    }
}
