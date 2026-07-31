<?php

namespace Modules\Payments\Gateways;

use App\Support\Money\Money;
use Modules\Payments\Contracts\PaymentGatewayInterface;
use Modules\Payments\DTOs\PaymentIntentResult;
use Modules\Payments\DTOs\RefundResult;
use Modules\Payments\Models\Payment;
use Stripe\StripeClient;

class StripeGateway implements PaymentGatewayInterface
{
    private StripeClient $client;

    public function __construct(?StripeClient $client = null)
    {
        // Injectable for tests (see tests/Feature/CheckoutTest.php, which
        // binds a fake StripeGateway instead of hitting the real API).
        $this->client = $client ?? new StripeClient(config('services.stripe.secret'));
    }

    public function createIntent(Money $amount, array $metadata = []): PaymentIntentResult
    {
        $intent = $this->client->paymentIntents->create([
            'amount' => $amount->amountMinor(),
            'currency' => strtolower($amount->currency()),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => $metadata,
        ]);

        return new PaymentIntentResult(
            providerReference: $intent->id,
            clientSecret: $intent->client_secret,
            status: $intent->status,
        );
    }

    public function refund(Payment $payment, Money $amount): RefundResult
    {
        $refund = $this->client->refunds->create([
            'payment_intent' => $payment->provider_reference,
            'amount' => $amount->amountMinor(),
        ]);

        return new RefundResult(
            providerReference: $refund->id,
            status: $refund->status,
        );
    }

    public function provider(): string
    {
        return 'stripe';
    }
}