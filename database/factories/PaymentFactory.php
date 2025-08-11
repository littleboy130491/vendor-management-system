<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => \App\Models\Invoice::factory(),
            'payment_reference' => 'PAY-' . $this->faker->unique()->numberBetween(10000, 999999),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'method' => $this->faker->randomElement(['bank_transfer', 'cheque', 'card', 'ach', 'wire']),
            'paid_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'processed_by' => \App\Models\User::factory(),
            'notes' => $this->faker->optional()->sentence(),
            'bank_details' => [
                'bank_name' => $this->faker->company . ' Bank',
                'account_number' => $this->faker->bankAccountNumber,
                'routing_number' => $this->faker->randomNumber(9, true),
                'reference' => $this->faker->optional()->uuid
            ]
        ];
    }
}
