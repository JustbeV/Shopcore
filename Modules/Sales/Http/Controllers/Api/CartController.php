<?php

namespace Modules\Sales\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Sales\Exceptions\InsufficientStockException;
use Modules\Sales\Http\Requests\AddCartItemRequest;
use Modules\Sales\Http\Requests\UpdateCartItemRequest;
use Modules\Sales\Http\Resources\CartResource;
use Modules\Sales\Models\Cart;
use Modules\Sales\Services\CartService;

/**
 * Storefront-facing cart endpoints. Auth is optional here (guest OR
 * `customer` guard) — see routes/api.php, this controller sits outside
 * `auth:customer` middleware and resolves identity itself.
 */
class CartController extends Controller
{
    public function __construct(
        private readonly CartService $carts,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        return $this->respond($cart);
    }

    public function storeItem(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        try {
            $this->carts->addItem($cart, $request->string('variant_id'), $request->integer('quantity'));
        } catch (InsufficientStockException $e) {
            return $this->stockErrorResponse($e);
        }

        return $this->respond($cart->fresh('items.variant'));
    }

    public function updateItem(UpdateCartItemRequest $request, string $itemId): JsonResponse
    {
        $cart = $this->resolveCart($request);

        try {
            $this->carts->updateQuantity($cart, $itemId, $request->integer('quantity'));
        } catch (InsufficientStockException $e) {
            return $this->stockErrorResponse($e);
        }

        return $this->respond($cart->fresh('items.variant'));
    }

    public function destroyItem(Request $request, string $itemId): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $this->carts->removeItem($cart, $itemId);

        return $this->respond($cart->fresh('items.variant'));
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $this->carts->clear($cart);

        return $this->respond($cart->fresh('items.variant'));
    }

    private function resolveCart(Request $request): Cart
    {
        if (Auth::guard('customer')->check()) {
            return $this->carts->resolveForCustomer(Auth::guard('customer')->user());
        }

        return $this->carts->resolveForGuest($request->header('X-Cart-Token'));
    }

    private function respond(Cart $cart): JsonResponse
    {
        $response = (new CartResource($cart))->response();

        // Guest carts: hand the token back so the client can replay it on the
        // next request. Customer carts need no token — the Sanctum session
        // cookie already identifies them.
        if ($cart->isGuestCart()) {
            $response->headers->set('X-Cart-Token', $cart->session_token);
        }

        return $response;
    }

    private function stockErrorResponse(InsufficientStockException $e): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'INSUFFICIENT_STOCK',
                'message' => 'The requested quantity is not available.',
                'fields' => [
                    'variant_id' => $e->variantId,
                    'requested' => $e->requested,
                    'available' => $e->available,
                ],
            ],
        ], 422);
    }
}
