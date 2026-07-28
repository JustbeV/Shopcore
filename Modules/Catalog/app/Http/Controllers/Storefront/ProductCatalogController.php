<?php

namespace Modules\Catalog\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductCatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = app('TenantContext')->getTenant();

        $products = $tenant->products()
            ->where('is_published', true)
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Storefront/Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(string $slug): Response
    {
        $tenant = app('TenantContext')->getTenant();

        $product = $tenant->products()
            ->where('is_published', true)
            ->where('slug', $slug)
            ->with(['variants', 'images', 'categories'])
            ->firstOrFail();

        return Inertia::render('Storefront/Products/Show', [
            'product' => $product,
        ]);
    }
}