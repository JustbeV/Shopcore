<?php

namespace Modules\Sales\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Payments\Models\Payment;
use Modules\Sales\Models\Order;
use Modules\Sales\Models\Refund;
use Modules\Tenant\Models\Store;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    protected $model = Refund::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'order_id' => Order::factory(),
            'payment_id' => Payment::factory(),
            'status' => 'requested',
            'amount_cents' => $this->faker->numberBetween(1000, 20000),
            'reason' => $this->faker->sentence(),
        ];
    }
}