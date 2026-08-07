<?php

namespace Modules\Sales\Listeners;

use App\Support\Tenancy\Tenancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Events\RefundRequested;
use Modules\Tenant\Models\Store;

class NotifyMerchantOfRefundRequest implements ShouldQueue
{
    public function handle(RefundRequested $event): void
    {
        Tenancy::run(Store::query()->findOrFail($event->storeId), function () use ($event) {
            // TODO(Notifications module): notify staff with 'orders.refund'.
            Log::info('Merchant refund-request notification would be sent', ['refund_id' => $event->refundId]);
        });
    }
}