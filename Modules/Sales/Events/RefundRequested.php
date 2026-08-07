<?php

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;

class RefundRequested
{
    use Dispatchable;

    public function __construct(
        public readonly string $storeId,
        public readonly string $refundId,
    ) {}
}