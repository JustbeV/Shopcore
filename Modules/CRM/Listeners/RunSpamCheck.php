<?php

namespace Modules\CRM\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\CRM\Events\ReviewSubmitted;

class RunSpamCheck implements ShouldQueue
{
    public function handle(ReviewSubmitted $event): void
    {
        // TODO: wire up a real spam-detection service. Reviews already
        // default to status=pending and require merchant approval to go
        // live (see ReviewPolicy/ReviewController), so this is a
        // defense-in-depth signal, not the only gate.
    }
}