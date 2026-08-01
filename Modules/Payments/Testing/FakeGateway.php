<?php

namespace Modules\Payments\Testing;

use App\Support\Money\Money;
use Illuminate\Support\Str;
use Modules\Payments\Contracts\PaymentGatewayInterface;
use Modules\Payments\DTOs\PaymentIntentResult;
use Modules\Payments\DTOs\RefundResult;
use Modules\Payments\Models\Payment;

class FakeGateway implements PaymentGatewayInterface
{
    /** @var callable|null */
    public static $onCreateIntent = null;

    public function createIntent(Money $amount, array $metadata = []): PaymentIntentResult
    {
        if (static::$onCreateIntent) {
            (static::$onCreateIntent)($amount, $metadata);
        }

        $id = 'pi_fake_'.Str::random(16);

        return new PaymentIntentResult(
            providerReference: $id,
            clientSecret: $id.'_secret_'.Str::random(10),
            status: 'requires_confirmation',
        );
    }

    public function refund(Payment $payment, Money $amount): RefundResult
    {
        return new RefundResult(providerReference: 're_fake_'.Str::random(16), status: 'succeeded');
    }

    public function provider(): string
    {
        return 'stripe';
    }

    public static function reset(): void
    {
        static::$onCreateIntent = null;
    }
}
