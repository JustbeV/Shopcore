<?php

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;

class OrderRefunded
{
    use Dispatchable;

    public function __construct(
        public readonly string $storeId,
        public readonly string $orderId,
        public readonly string $refundId,
    ) {}
}