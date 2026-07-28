<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Tenant\app\Models\Store;
use Symfony\Component\HttpFoundation\Response;

/**
 * Real implementation, replacing the Phase 0 structural stub.
 * Resolves the current Store from the Host header — either the
 * platform subdomain (`{slug}.shopcore.test`) or a verified custom
 * domain (`store_domains.hostname`) — caches the lookup in Redis
 * (architecture §4.1 sequence diagram), and binds it into
 * TenantContext for the rest of the request.
 *
 * Central/admin domains (config('tenancy.central_domains')) are
 * skipped entirely — no tenant to resolve for admin.shopcore.test.
 */
final class IdentifyTenant
{
    public function __construct(
        private readonly TenantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (in_array($host, config('tenancy.central_domains', []), true)) {
            return $next($request);
        }

        $store = $this->resolve($host);

        if ($store === null) {
            abort(404, 'Store not found.');
        }

        if ($store->status === Store::STATUS_SUSPENDED) {
            abort(403, 'This store has been suspended.');
        }

        if ($store->status === Store::STATUS_CLOSED) {
            abort(404, 'Store not found.');
        }

        // ------------------------------------------------------------------
        // Publish Gate Enforcement
        // ------------------------------------------------------------------
        // If the store is draft/unpublished, block public storefront access.
        // (Optionally allow logged-in store owners/staff to preview via an auth check here)
        if (! $store->is_published) {
            abort(404, 'Store not found.'); // Or render a custom 'Store Opening Soon' view
        }

        $this->context->set($store);

        return $next($request);
    }

    private function resolve(string $host): ?Store
    {
        $cacheKey = "tenant:host:{$host}";

        $storeId = Cache::store(config('tenancy.cache.store'))
            ->remember($cacheKey, config('tenancy.cache.ttl_seconds'), function () use ($host): string|false {
                $store = Store::query()
                    ->where('domain', $host)
                    ->orWhereHas('domains', fn ($q) => $q->where('hostname', $host)->where('verification_status', 'verified'))
                    ->first();

                // Cache a sentinel for "not found" too, so a flood of
                // requests to a bogus/typo'd domain doesn't hammer the
                // database on every single request.
                return $store?->id ?? false;
            });

        if ($storeId === false) {
            return null;
        }

        return Store::query()->find($storeId);
    }
}