<?php

namespace Modules\Sales\Exceptions;

use Exception;

/**
 * Thrown when a cart mutation (add/update quantity) would exceed the
 * variant's available-to-sell quantity.
 *
 * This is a *soft*, UX-facing check at add-to-cart time — it reads current
 * stock but reserves nothing. It intentionally does NOT guarantee stock is
 * still available a minute later (another customer can buy it out from under
 * this cart). The authoritative, reservation-backed check happens in
 * CheckoutService/InventoryService (Phase 5, Checkout pass) and is the one
 * that actually decides whether an order can be placed.
 */
class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly string $variantId,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct(
            "Requested quantity ({$requested}) for variant {$variantId} exceeds available stock ({$available})."
        );
    }
}
