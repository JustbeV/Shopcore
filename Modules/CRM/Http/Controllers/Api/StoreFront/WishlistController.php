<?php

namespace Modules\CRM\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Catalog\Models\Product;
use Modules\CRM\Models\Wishlist;

class WishlistController extends Controller
{
    // Mounted behind auth:customer in routes/api.php.

    public function index()
    {
        $customer = Auth::guard('customer')->user();

        $productIds = Wishlist::query()
            ->where('customer_id', $customer->id)
            ->pluck('product_id');

        return response()->json([
            'data' => Product::query()->whereIn('id', $productIds)->get(['id', 'title', 'slug', 'base_price_cents']),
        ]);
    }

    public function store(string $productId)
    {
        $customer = Auth::guard('customer')->user();
        Product::query()->findOrFail($productId); // 404s cleanly if it doesn't exist/isn't this store's

        Wishlist::query()->firstOrCreate([
            'customer_id' => $customer->id,
            'product_id' => $productId,
        ]);

        return response()->json(['data' => ['message' => 'Added to wishlist.']], 201);
    }

    public function destroy(string $productId)
    {
        $customer = Auth::guard('customer')->user();

        Wishlist::query()
            ->where('customer_id', $customer->id)
            ->where('product_id', $productId)
            ->delete();

        return response()->json(['data' => ['message' => 'Removed from wishlist.']]);
    }
}