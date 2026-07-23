<?php
// This is the Eloquent scope that automatically adds WHERE store_id = ? 
// to all tenant-owned queries.
namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->check()) {
            $builder->where($model->getTable() . '.store_id', $context->id());
        }
    }
}