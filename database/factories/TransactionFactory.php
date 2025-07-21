<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item' => fake()->words(3, true),
            'date' => fake()->date(),
            'quantity' => fake()->randomFloat(2, 1, 100),
            'value' => fake()->randomFloat(2, 10, 1000),
            'wallet_id' => \App\Models\Wallet::factory(),
        ];
    }
}
