<?php

namespace Modules\CRM\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ReviewSubmitted
{
    use Dispatchable;

    public function __construct(
        public readonly string $storeId,
        public readonly string $reviewId,
    ) {}
}