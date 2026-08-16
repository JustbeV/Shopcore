<?php

namespace Modules\Shipping\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shipping\Models\ShippingRate;
use Modules\Tenant\Models\Store;

/**
 * @extends Factory<ShippingRate>
 */
class ShippingRateFactory extends Factory
{
    protected $model = ShippingRate::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => 'Standard Shipping',
            'country_code' => 'ALL',
            'price_cents' => 500,
            'is_active' => true,
        ];
    }
}