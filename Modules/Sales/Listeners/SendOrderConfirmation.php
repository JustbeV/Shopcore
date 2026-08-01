<?php

namespace Modules\Sales\Listeners;

use App\Support\Tenancy\Tenancy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Events\OrderPlaced;
use Modules\Sales\Models\Order;
use Modules\Tenant\Models\Store;

class SendOrderConfirmation implements ShouldQueue
{
    public string $queue = 'orders';

    public function handle(OrderPlaced $event): void
    {
        // Queued listener = fresh process, no tenant bound yet (see
        // OrderPlaced's docblock) — always re-establish context first.
        Tenancy::run(Store::query()->findOrFail($event->storeId), function () use ($event) {
            $order = Order::query()->with('items', 'customer')->findOrFail($event->orderId);

            // TODO(Notifications module, Phase 6): replace with
            // NotificationService::send($order->customer, 'order_confirmation', [...]).
            // Logged for now so the event wiring is verifiable end-to-end
            // before the Notifications module exists.
            Log::info('Order confirmation email would be sent', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $order->customer_id,
            ]);
        });
    }
}
