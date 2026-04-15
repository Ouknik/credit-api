<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password', // Will be hashed automatically
            'balance' => fake()->randomFloat(2, 100, 5000),
            'status' => 'active',
            'role' => Shop::ROLE_SHOP_OWNER,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    public function withBalance(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $amount,
        ]);
    }

    public function distributor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Shop::ROLE_DISTRIBUTOR,
        ]);
    }

    public function shopOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Shop::ROLE_SHOP_OWNER,
        ]);
    }
}
