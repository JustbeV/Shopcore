<?php

namespace Modules\Sales\Listeners;

use App\Support\Tenancy\Tenancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Events\OrderRefunded;
use Modules\Tenant\Models\Store;

class SendRefundConfirmation implements ShouldQueue
{
    public function handle(OrderRefunded $event): void
    {
        Tenancy::run(Store::query()->findOrFail($event->storeId), function () use ($event) {
            // TODO(Notifications module): send the actual confirmation email.
            Log::info('Refund confirmation email would be sent', ['order_id' => $event->orderId, 'refund_id' => $event->refundId]);
        });
    }
}