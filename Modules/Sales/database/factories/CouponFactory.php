<?php

namespace Modules\Sales\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Sales\Models\Coupon;
use Modules\Tenant\Models\Store;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'code' => strtoupper(Str::random(8)),
            'type' => 'percentage',
            'value' => 10,
            'usage_limit' => null,
            'times_used' => 0,
            'is_active' => true,
        ];
    }

    public function fixed(int $cents): static
    {
        return $this->state(['type' => 'fixed', 'value' => $cents]);
    }

    public function freeShipping(): static
    {
        return $this->state(['type' => 'free_shipping', 'value' => 0]);
    }

    public function limitedTo(int $uses): static
    {
        return $this->state(['usage_limit' => $uses]);
    }
}