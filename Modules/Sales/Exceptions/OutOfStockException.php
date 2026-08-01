<?php

namespace Modules\Sales\Exceptions;

use Exception;

/**
 * Thrown by InventoryService::reserve() when a checkout attempt cannot be
 * fully satisfied. Unlike InsufficientStockException (cart-level, advisory),
 * this one backs a real reservation attempt and is what actually blocks an
 * order from being created (§8.2: "insufficient stock -> 422 item unavailable").
 */
class OutOfStockException extends Exception
{
    public function __construct(
        public readonly string $variantId,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct(
            "Cannot reserve {$requested} of variant {$variantId}; only {$available} available."
        );
    }
}
