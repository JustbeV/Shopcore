<?php

namespace Modules\Payments\DTOs;

/**
 * Immutable result of PaymentGatewayInterface::createIntent().
 *
 * `clientSecret` is intentionally generic (not `stripe_client_secret`) even
 * though only Stripe is wired up right now — PayPal's equivalent (an
 * "order id" the client-side SDK needs) fits the same shape without
 * renaming this DTO when that gateway is added.
 */
final readonly class PaymentIntentResult
{
    public function __construct(
        public string $providerReference,
        public string $clientSecret,
        public string $status,
    ) {}
}