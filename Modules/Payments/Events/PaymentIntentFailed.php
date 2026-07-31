<?php

namespace Modules\Payments\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PaymentIntentFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $providerReference,
        public readonly string $provider = 'stripe',
        public readonly ?string $reason = null,
    ) {}
}