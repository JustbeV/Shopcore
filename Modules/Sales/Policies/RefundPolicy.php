<?php

namespace Modules\Sales\Policies;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Modules\Sales\Models\Refund;

class RefundPolicy
{
    public function decide(User $user, Refund $refund): bool
    {
        return $user->can('orders.refund')
            && $refund->store_id === app(TenantContext::class)->store->id
            && $refund->status === 'requested';
    }
}