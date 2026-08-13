<?php

namespace Modules\Shipping\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Shipping\Models\ShippingRate
 */
class ShippingRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'country_code' => $this->country_code,
            'price_cents' => $this->price_cents,
            'is_active' => $this->is_active,
        ];
    }
}   