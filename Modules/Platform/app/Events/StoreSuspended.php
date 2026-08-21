<?php

namespace Modules\Platform\Events;

use Illuminate\Foundation\Events\Dispatchable;

class StoreSuspended
{
    use Dispatchable;

    public function __construct(
        public readonly string $storeId,
    ) {}
}