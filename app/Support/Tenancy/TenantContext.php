<?php
// This class holds the currently active store during any web request.

namespace App\Support\Tenancy;

use App\Models\Store;

class TenantContext
{
    protected ?Store $tenant = null;

    public function set(?Store $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Store
    {
        return $this->tenant;
    }

    public function id(): ?string
    {
        return $this->tenant?->id;
    }

    public function check(): bool
    {
        return $this->tenant !== null;
    }
}