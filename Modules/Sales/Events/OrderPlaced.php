<?php

namespace Modules\Sales\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Despite the name (kept consistent with §12's events table), this fires
 * after payment succeeds, not at checkout initiation — an order isn't
 * "placed" from the merchant's or customer's point of view until it's paid.
 * A pending/unpaid order that never completes payment quietly expires
 * (see ReleaseExpiredReservations scheduled command, noted as a TODO in the
 * README) without ever firing this event.
 */
class OrderPlaced
{
    use Dispatchable, SerializesModels;

    // Deliberately carries IDs, not the loaded Order model — this event is
    // queued (see EventServiceProvider registration notes in README), and
    // queued listeners run in a fresh worker process with no tenant bound
    // yet. Listeners must re-establish tenant context themselves via
    // TenantContext::run($storeId, ...) before touching any scoped model.
    public function __construct(
        public readonly string $storeId,
        public readonly string $orderId,
    ) {}
}