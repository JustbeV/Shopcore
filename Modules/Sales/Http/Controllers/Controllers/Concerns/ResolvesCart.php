<?php

namespace Modules\Sales\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Sales\Models\Cart;
use Modules\Sales\Services\CartService;

trait ResolvesCart
{
    private function resolveCart(Request $request): Cart
    {
        /** @var CartService $carts */
        $carts = app(CartService::class);

        if (Auth::guard('customer')->check()) {
            return $carts->resolveForCustomer(Auth::guard('customer')->user());
        }

        return $carts->resolveForGuest($request->header('X-Cart-Token'));
    }
}
