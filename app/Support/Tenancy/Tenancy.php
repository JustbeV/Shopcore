<?php

namespace App\Support\Tenancy;

use Modules\Tenant\Models\Store;

class Tenancy
{
    /**
     * Binds $store as the current tenant for the duration of $callback, then
     * restores whatever was bound before (usually nothing, outside an HTTP
     * request). Used by queued listeners and the Stripe webhook controller's
     * downstream listeners — see Modules/Sales/Services/CheckoutService.php
     * for the canonical example.
     */
    public static function run(Store $store, callable $callback): mixed
    {
        $context = app(TenantContext::class);
        $hadPrevious = $context->has();
        $previous = $hadPrevious ? $context->store : null;

        $context->set($store);

        try {
            return $callback();
        } finally {
            if ($hadPrevious) {
                $context->set($previous);
            } else {
                $context->clear();
            }
        }
    }
}