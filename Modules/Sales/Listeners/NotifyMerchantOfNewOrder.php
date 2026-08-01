<?php

namespace Modules\Sales\Listeners;

use App\Support\Tenancy\Tenancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Events\OrderPlaced;
use Modules\Sales\Models\Order;
use Modules\Tenant\Models\Store;

class NotifyMerchantOfNewOrder implements ShouldQueue
{
    public string $queue = 'orders';

    public function handle(OrderPlaced $event): void
    {
        Tenancy::run(Store::query()->findOrFail($event->storeId), function () use ($event) {
            $order = Order::query()->findOrFail($event->orderId);

            // TODO(Notifications module): notify owner + staff with
            // "orders.manage" permission via their preferred channel.
            Log::info('Merchant new-order notification would be sent', [
                'order_id' => $order->id,
                'store_id' => $event->storeId,
            ]);
        });
    }
}
