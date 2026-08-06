<?php

namespace Modules\CRM\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Product;
use Modules\CRM\Models\Customer;
use Modules\CRM\Models\Review;
use Modules\Tenant\Models\Store;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'product_id' => Product::factory(),
            'customer_id' => Customer::factory(),
            'order_id' => null,
            'rating' => $this->faker->numberBetween(1, 5),
            'body' => $this->faker->paragraph(),
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }
}