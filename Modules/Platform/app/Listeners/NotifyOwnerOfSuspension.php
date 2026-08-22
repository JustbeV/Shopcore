<?php

namespace Modules\Platform\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Platform\Events\StoreSuspended;

class NotifyOwnerOfSuspension implements ShouldQueue
{
    public function handle(StoreSuspended $event): void
    {
        // TODO(Notifications module): email the store owner.
        Log::info('Store suspension notification would be sent', ['store_id' => $event->storeId]);
    }
}