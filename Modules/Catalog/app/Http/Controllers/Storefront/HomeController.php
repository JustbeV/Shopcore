<?php

namespace Modules\Catalog\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $tenant = app('TenantContext')->getTenant();

        // Sample query scoped to current tenant context
        $featuredProducts = $tenant->products()
            ->where('is_published', true)
            ->where('is_featured', true)
            ->take(8)
            ->get();

        return Inertia::render('Storefront/Home', [
            'featuredProducts' => $featuredProducts,
        ]);
    }
}