<?php

namespace Database\Factories;

use App\Models\Recharge;
use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RechargeFactory extends Factory
{
    protected $model = Recharge::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'shop_id' => Shop::factory(),
            'customer_id' => fake()->optional(0.7)->passthrough(Customer::factory()),
            'phone' => '+212' . fake()->numerify('#########'),
            'operator' => fake()->randomElement(['maroc_telecom', 'inwi', 'orange']),
            'amount' => fake()->randomElement([5, 10, 20, 50, 100]),
            'status' => 'success',
            'reference_code' => 'RCH-' . strtoupper(Str::random(12)),
            'idempotency_key' => Str::uuid()->toString(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    public function forOperator(string $operator): static
    {
        return $this->state(fn (array $attributes) => [
            'operator' => $operator,
        ]);
    }
}
