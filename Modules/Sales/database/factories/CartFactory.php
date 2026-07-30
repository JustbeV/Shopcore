<?php

namespace Modules\Sales\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\CRM\Models\Customer;
use Modules\Sales\Models\Cart;
use Modules\Tenant\Models\Store;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'session_token' => (string) Str::ulid(),
            'currency' => 'USD',
            'expires_at' => now()->addDays(7),
        ];
    }

    public function forCustomer(?Customer $customer = null): static
    {
        return $this->state(fn (array $attrs) => [
            'customer_id' => ($customer ?? Customer::factory()->create(['store_id' => $attrs['store_id']]))->id,
            'session_token' => null,
            'expires_at' => now()->addDays(30),
        ]);
    }
}
