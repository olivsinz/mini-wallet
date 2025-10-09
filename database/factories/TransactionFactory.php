<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
final class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 10, 1000);
        $commissionFee = $amount * 0.15;
        $totalDeducted = $amount + $commissionFee;

        return [
            'idempotency_key' => fake()->uuid(),
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'amount' => $amount,
            'commission_fee' => $commissionFee,
            'total_deducted' => $totalDeducted,
            'status' => 'completed',
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function between(User $sender, User $receiver): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);
    }

    public function withAmount(float $amount): static
    {
        $commissionFee = 0.15;

        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'commission_fee' => $commissionFee,
            'total_deducted' => $amount + $commissionFee,
        ]);
    }
}
