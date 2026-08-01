<?php

namespace Modules\Sales\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Sales\Events\OrderPlaced;

class UpdateSalesAnalytics implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(OrderPlaced $event): void
    {
        // TODO(Reporting module, Phase 7): increment/refresh the relevant
        // materialized views (daily sales, top products, etc.). Left as a
        // no-op stub so the OrderPlaced -> listener wiring is already
        // correct and just needs a body once Reporting exists.
    }
}
