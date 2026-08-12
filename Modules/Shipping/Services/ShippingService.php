<?php

namespace Modules\Shipping\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Shipping\Models\ShippingRate;

class ShippingService
{
    /**
     * Country-specific rates take priority over 'ALL' (store-wide default)
     * rates when both exist, but both are returned — let the customer pick.
     */
    public function ratesFor(string $countryCode): Collection
    {
        return ShippingRate::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('country_code', strtoupper($countryCode))->orWhere('country_code', 'ALL'))
            ->orderBy('price_cents')
            ->get();
    }

    public function findValidRate(string $rateId, string $countryCode): ?ShippingRate
    {
        return $this->ratesFor($countryCode)->firstWhere('id', $rateId);
    }
}