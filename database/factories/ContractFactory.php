<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        $endDate = $this->faker->dateTimeBetween($startDate, '+2 years');
        
        return [
            'contract_number' => 'CON-' . now()->year . '-' . str_pad($this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'vendor_id' => \App\Models\Vendor::factory(),
            'rfq_id' => null,
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $this->faker->randomElement(['draft', 'active', 'expired', 'terminated', 'renewed']),
            'terms' => $this->faker->paragraphs(3, true),
            'contract_value' => $this->faker->randomFloat(2, 1000, 1000000),
            'deliverables' => [
                $this->faker->sentence(),
                $this->faker->sentence(),
                $this->faker->sentence()
            ],
            'payment_terms' => [
                'net_days' => $this->faker->randomElement([15, 30, 45, 60]),
                'early_payment_discount' => $this->faker->randomFloat(1, 0, 5),
                'late_payment_penalty' => $this->faker->randomFloat(1, 0, 3)
            ],
            'created_by' => \App\Models\User::factory()
        ];
    }
}
