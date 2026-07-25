<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Storefront-only guard: an active-but-unpublished store (owner still
 * building it) should 404 to public visitors, while remaining fully
 * editable in the merchant dashboard. Applied to storefront route
 * groups only — never to /admin dashboard routes.
 */
final class EnsureStorePublished
{
    public function __construct(
        private readonly TenantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $store = $this->context->getOrFail();

        if (! $store->is_published) {
            abort(404, 'Store not found.');
        }

        return $next($request);
    }
}