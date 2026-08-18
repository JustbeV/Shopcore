<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantScope;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (! $model->store_id) {
                $context = app(TenantContext::class);

                if ($context->has()) {
                    $model->store_id = $context->store->id;
                }
                // If no tenant is bound at create time either, store_id is
                // left null and the DB's NOT NULL constraint on store_id
                // (present on every tenant-owned table) catches it — same
                // "fail loud" philosophy as TenantScope::apply().
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(\Modules\Tenant\Models\Store::class);
    }
}