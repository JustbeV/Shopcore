<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantScope;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            $context = app(TenantContext::class);
            if ($context->check() && ! $model->store_id) {
                $model->store_id = $context->id();
            }
        });
    }
}