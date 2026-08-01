<?php

namespace Modules\Sales\Policies;

use App\Models\User;
use Modules\Sales\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.view') || $user->can('orders.manage');
    }

    public function view(User $user, Order $order): bool
    {
        // The global TenantScope already guarantees $order belongs to the
        // current store for any query the controller could have run — this
        // re-check is the "even if a scope were bypassed" defense from §13,
        // not load-bearing under normal operation.
        return ($user->can('orders.view') || $user->can('orders.manage'))
            && $order->store_id === app(\App\Support\Tenancy\TenantContext::class)->store->id;
    }

    public function fulfill(User $user, Order $order): bool
    {
        return $user->can('orders.manage')
            && $order->store_id === app(\App\Support\Tenancy\TenantContext::class)->store->id
            && in_array($order->status, ['paid', 'processing'], strict: true);
    }
}
