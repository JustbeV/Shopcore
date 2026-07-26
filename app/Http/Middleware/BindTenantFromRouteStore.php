<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\app\Models\Store;
use Symfony\Component\HttpFoundation\Response;

/**
 * IdentifyTenant (Phase 2) resolves the tenant from the Host header —
 * correct for the public storefront, where the domain IS the tenant.
 * Merchant-dashboard routes work differently: they're reached from a
 * generic dashboard domain and identify the store via an explicit
 * `{store}` route parameter (see Modules/Tenant/routes/api.php).
 *
 * Without this middleware, TenantContext stays empty on those routes,
 * BelongsToTenant's auto-fill silently does nothing, and TenantScope
 * filters nothing — every Catalog query would be unscoped. This is
 * the fix, applied to every module route group that identifies its
 * store via `{store}` rather than the Host header.
 */
final class BindTenantFromRouteStore
{
    public function __construct(
        private readonly TenantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->route('store');

        if ($store instanceof Store) {
            $this->context->set($store);
        }

        return $next($request);
    }
}