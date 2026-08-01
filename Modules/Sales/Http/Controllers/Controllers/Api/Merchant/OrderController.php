<?php

namespace Modules\Sales\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Sales\Http\Requests\FulfillOrderRequest;
use Modules\Sales\Http\Resources\OrderResource;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\Shipment;

class OrderController extends Controller
{
    // Mounted behind auth:sanctum + the merchant/staff team-permission
    // middleware in routes/api.php. Policy authorization happens per-action
    // below via $this->authorize(), per §5.3's layering.

    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('placed_at')
            ->paginate($request->integer('per_page', 20));

        return OrderResource::collection($orders);
    }

    public function show(string $orderId)
    {
        $order = Order::query()->with('items', 'shipment', 'statusHistory')->findOrFail($orderId);

        $this->authorize('view', $order);

        return new OrderResource($order);
    }

    public function fulfill(FulfillOrderRequest $request, string $orderId)
    {
        $order = Order::query()->findOrFail($orderId);

        // Authorization already ran in FulfillOrderRequest::authorize(),
        // which checks the same 'fulfill' policy ability against this order.

        $shipment = Shipment::query()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'carrier' => $request->input('carrier'),
                'tracking_number' => $request->input('tracking_number'),
                'status' => 'shipped',
                'shipped_at' => now(),
            ],
        );

        $order->transitionTo('shipped', changedBy: $request->user()->id);

        return new OrderResource($order->fresh(['items', 'shipment']));
    }
}
