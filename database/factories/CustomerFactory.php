<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'shop_id' => Shop::factory(),
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'total_debt' => 0,
            'is_trusted' => fake()->boolean(30),
            'daily_limit' => fake()->optional(0.3)->randomFloat(2, 50, 500),
            'monthly_limit' => fake()->optional(0.3)->randomFloat(2, 500, 2000),
            'max_debt_limit' => fake()->optional(0.5)->randomFloat(2, 200, 1000),
        ];
    }

    public function trusted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trusted' => true,
        ]);
    }

    public function withDebt(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'total_debt' => $amount,
        ]);
    }
}
