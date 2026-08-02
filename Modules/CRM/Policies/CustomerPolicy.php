<?php

namespace Modules\CRM\Policies;

use Modules\CRM\Models\Customer;

class CustomerPolicy
{
    public function view(Customer $actor, Customer $target): bool
    {
        return $actor->id === $target->id;
    }

    public function update(Customer $actor, Customer $target): bool
    {
        return $actor->id === $target->id;
    }
}
