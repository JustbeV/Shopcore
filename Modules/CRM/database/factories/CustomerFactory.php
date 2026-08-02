<?php

namespace Modules\CRM\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Modules\CRM\Models\Customer;
use Modules\Tenant\Models\Store;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('Password1234!'),
            'phone' => $this->faker->e164PhoneNumber(),
            'email_verified_at' => now(),
        ];
    }

    public function guest(): static
    {
        return $this->state([
            'password' => null,
            'email_verified_at' => null,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
