<?php

namespace Modules\Payments\Contracts;

use App\Support\Money\Money;
use Modules\Payments\DTOs\PaymentIntentResult;
use Modules\Payments\DTOs\RefundResult;
use Modules\Payments\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Create a payment intent for the given amount. Returns the provider's
     * reference id plus whatever client-side secret/token the storefront
     * needs to complete payment (e.g. Stripe's client_secret).
     */
    public function createIntent(Money $amount, array $metadata = []): PaymentIntentResult;

    public function refund(Payment $payment, Money $amount): RefundResult;

    /**
     * Identifies which `payments.provider` enum value this gateway handles.
     * Used by GatewayResolver to pick the right implementation.
     */
    public function provider(): string;
}