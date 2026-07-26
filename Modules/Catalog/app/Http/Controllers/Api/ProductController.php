<?php

declare(strict_types=1);

namespace Modules\Catalog\app\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Catalog\app\Http\Requests\StoreProductRequest;
use Modules\Catalog\app\Http\Requests\UpdateProductRequest;
use Modules\Catalog\app\Http\Resources\ProductResource;
use Modules\Catalog\app\Models\Product;
use Modules\Catalog\app\Services\ProductService;
use Modules\Tenant\app\Models\Store;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
    ) {}

    /**
     * GET /api/v1/tenant/stores/{store}/products
     */
    public function index(Request $request, Store $store): JsonResponse
    {
        Gate::authorize('viewAny', [Product::class, $store->id]);

        $products = Product::query()
            ->with(['images', 'categories'])
            ->withCount('variants')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'ilike', '%'.$request->string('search').'%'))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => ['pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ]],
        ]);
    }

    /**
     * POST /api/v1/tenant/stores/{store}/products
     */
    public function store(StoreProductRequest $request, Store $store): JsonResponse
    {
        $product = $this->products->create($store, $request->validated());

        return (new ProductResource($product->load(['images', 'categories'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/v1/tenant/stores/{store}/products/{product}
     */
    public function show(Store $store, Product $product): ProductResource
    {
        Gate::authorize('view', $product);

        return new ProductResource($product->load(['variants.inventory', 'images', 'categories']));
    }

    /**
     * PUT /api/v1/tenant/stores/{store}/products/{product}
     */
    public function update(UpdateProductRequest $request, Store $store, Product $product): ProductResource
    {
        $product = $this->products->update($product, $request->validated());

        return new ProductResource($product->load(['images', 'categories']));
    }

    /**
     * DELETE /api/v1/tenant/stores/{store}/products/{product}
     */
    public function destroy(Store $store, Product $product): JsonResponse
    {
        Gate::authorize('delete', $product);

        $this->products->delete($product);

        return response()->json(null, 204);
    }

    /**
     * POST /api/v1/tenant/stores/{store}/products/{product}/publish
     */
    public function publish(Store $store, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        try {
            $product = $this->products->publish($product);
        } catch (DomainException $exception) {
            return response()->json(['error' => ['code' => 'PRODUCT_NOT_PUBLISHABLE', 'message' => $exception->getMessage()]], 422);
        }

        return response()->json(['data' => new ProductResource($product)]);
    }

    /**
     * POST /api/v1/tenant/stores/{store}/products/{product}/archive
     */
    public function archive(Store $store, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $product = $this->products->archive($product);

        return response()->json(['data' => new ProductResource($product)]);
    }
}