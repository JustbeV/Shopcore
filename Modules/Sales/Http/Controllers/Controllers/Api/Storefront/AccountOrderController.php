<?php

namespace Modules\Sales\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Sales\Http\Resources\OrderResource;
use Modules\Sales\Models\Order;

class AccountOrderController extends Controller
{
    // Mounted behind auth:customer in routes/api.php — no guest access,
    // unlike Cart/Checkout.

    public function index(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->latest('placed_at')
            ->paginate($request->integer('per_page', 20));

        return OrderResource::collection($orders);
    }

    public function show(Request $request, string $orderId)
    {
        $customer = Auth::guard('customer')->user();

        $order = Order::query()
            ->where('customer_id', $customer->id)
            ->with('items', 'shipment')
            ->findOrFail($orderId);

        return new OrderResource($order);
    }
}
