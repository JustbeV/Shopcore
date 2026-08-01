<?php

namespace Modules\Payments\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Payments\Models\Payment;
use Modules\Sales\Models\Order;
use Modules\Tenant\Models\Store;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'order_id' => Order::factory(),
            'provider' => 'stripe',
            'status' => 'pending',
            'provider_reference' => 'pi_'.Str::random(24),
            'client_secret' => 'pi_'.Str::random(24).'_secret_'.Str::random(10),
            'amount_cents' => $this->faker->numberBetween(1000, 50000),
            'currency' => 'USD',
        ];
    }

    public function succeeded(): static
    {
        return $this->state(['status' => 'succeeded']);
    }
}
