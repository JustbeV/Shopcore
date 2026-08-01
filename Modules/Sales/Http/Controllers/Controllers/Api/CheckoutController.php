<?php

namespace Modules\Sales\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\CRM\Models\Customer;
use Modules\Sales\Exceptions\CheckoutException;
use Modules\Sales\Exceptions\OutOfStockException;
use Modules\Sales\Http\Controllers\Concerns\ResolvesCart;
use Modules\Sales\Http\Requests\InitiateCheckoutRequest;
use Modules\Sales\Http\Resources\OrderResource;
use Modules\Sales\Services\CartService;
use Modules\Sales\Services\CheckoutService;

class CheckoutController extends Controller
{
    use ResolvesCart;

    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly CartService $carts,
    ) {}

    public function initiate(InitiateCheckoutRequest $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $customer = $this->resolveCustomer($request);

        try {
            $result = $this->checkout->initiate(
                cart: $cart,
                customer: $customer,
                shippingAddress: $request->input('shipping_address'),
                billingAddress: $request->billingAddress(),
                idempotencyKey: $request->header('Idempotency-Key'),
            );
        } catch (OutOfStockException $e) {
            return response()->json([
                'error' => [
                    'code' => 'OUT_OF_STOCK',
                    'message' => 'One or more items are no longer available in the requested quantity.',
                    'fields' => [
                        'variant_id' => $e->variantId,
                        'requested' => $e->requested,
                        'available' => $e->available,
                    ],
                ],
            ], 422);
        } catch (CheckoutException $e) {
            return response()->json([
                'error' => ['code' => $e->errorCode, 'message' => $e->getMessage()],
            ], 422);
        }

        // Cart is only cleared on a genuinely fresh order. On an idempotent
        // replay the cart was already emptied by the original successful
        // attempt, so this is a harmless no-op — but we still gate it on
        // the cart having items to avoid an unnecessary write.
        if ($cart->items->isNotEmpty()) {
            $this->carts->clear($cart);
        }

        return (new OrderResource($result['order']->load('items')))
            ->additional(['meta' => ['client_secret' => $result['client_secret']]])
            ->response()
            ->setStatusCode(201);
    }

    private function resolveCustomer(InitiateCheckoutRequest $request): Customer
    {
        if (Auth::guard('customer')->check()) {
            return Auth::guard('customer')->user();
        }

        // Guest checkout: find-or-create a customer record by email, with no
        // password set. They can later "claim" this account by registering
        // with the same email (left as a TODO for the CRM module — the
        // schema already supports it since `password` is nullable).
        return Customer::query()->firstOrCreate(
            ['email' => $request->input('customer_email')],
            ['default_address' => $request->input('shipping_address')],
        );
    }
}
