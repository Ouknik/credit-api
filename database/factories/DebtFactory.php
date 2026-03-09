<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DebtFactory extends Factory
{
    protected $model = Debt::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'shop_id' => Shop::factory(),
            'customer_id' => Customer::factory(),
            'amount' => fake()->randomFloat(2, 10, 200),
            'type' => fake()->randomElement(['manual', 'recharge']),
            'description' => fake()->optional(0.7)->sentence(),
        ];
    }

    public function payment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'payment',
            'description' => 'Payment received',
        ]);
    }

    public function recharge(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'recharge',
        ]);
    }
}
