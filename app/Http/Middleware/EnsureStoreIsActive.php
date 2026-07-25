<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\app\Models\Store;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards merchant-dashboard routes (product editing, settings, etc.)
 * that should be unreachable while a store is still `pending_setup`
 * — distinct from IdentifyTenant's suspended/closed checks, which
 * apply to the public storefront. A merchant mid-onboarding should
 * see the setup wizard, not a 403.
 */
final class EnsureStoreIsActive
{
    public function __construct(
        private readonly TenantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $store = $this->context->getOrFail();

        if ($store->status !== Store::STATUS_ACTIVE) {
            abort(409, 'This action is unavailable until your store has completed setup and been activated.');
        }

        return $next($request);
    }
}