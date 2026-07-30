<?php

namespace Modules\Sales\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\ProductVariant;
use Modules\Sales\Models\Cart;
use Modules\Sales\Models\CartItem;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity' => $this->faker->numberBetween(1, 3),
            'unit_price_cents' => $this->faker->numberBetween(500, 20000),
        ];
    }
}
