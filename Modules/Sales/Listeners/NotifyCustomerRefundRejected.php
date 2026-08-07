<?php

namespace Modules\Sales\Listeners;

use App\Support\Tenancy\Tenancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Events\RefundRejected;
use Modules\Tenant\Models\Store;

class NotifyCustomerRefundRejected implements ShouldQueue
{
    public function handle(RefundRejected $event): void
    {
        Tenancy::run(Store::query()->findOrFail($event->storeId), function () use ($event) {
            // TODO(Notifications module): send the actual email.
            Log::info('Refund rejection email would be sent', ['refund_id' => $event->refundId]);
        });
    }
}