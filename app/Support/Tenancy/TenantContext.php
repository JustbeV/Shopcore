<?php

namespace App\Support\Tenancy;

use Modules\Tenant\Models\Store;
use RuntimeException;

class TenantContext
{
    private ?Store $store = null;

    public function set(Store $store): void
    {
        $this->store = $store;
    }

    public function clear(): void
    {
        $this->store = null;
    }

    public function has(): bool
    {
        return $this->store !== null;
    }

    public function __get(string $name): mixed
    {
        if ($name === 'store') {
            if ($this->store === null) {
                throw new RuntimeException(
                    'No tenant is currently bound. HTTP requests must go through IdentifyTenant middleware; '.
                    'console commands/queue workers must wrap tenant-scoped work in Tenancy::run($store, ...).'
                );
            }

            return $this->store;
        }

        throw new RuntimeException("Undefined property [{$name}] on TenantContext.");
    }
}