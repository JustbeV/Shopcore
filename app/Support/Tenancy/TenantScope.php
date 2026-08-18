<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if (! $context->has()) {
            throw new \RuntimeException(
                'Querying a tenant-scoped model ('.get_class($model).') with no tenant bound. '.
                'Bind one via IdentifyTenant middleware or Tenancy::run($store, ...), or use '.
                '->withoutGlobalScope(\App\Support\Tenancy\TenantScope::class) if cross-tenant '.
                'access here is genuinely intentional.'
            );
        }

        $builder->where($model->getTable().'.store_id', $context->store->id);
    }
}