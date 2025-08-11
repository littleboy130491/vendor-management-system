<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $invoiceDate = $this->faker->dateTimeBetween('-3 months', 'now');
        $dueDate = $this->faker->dateTimeBetween($invoiceDate, '+60 days');
        $submittedAt = $this->faker->dateTimeBetween($invoiceDate, 'now');
        
        return [
            'invoice_number' => 'INV-' . $this->faker->unique()->numberBetween(1000, 99999),
            'vendor_id' => \App\Models\Vendor::factory(),
            'purchase_order_id' => null,
            'amount' => $this->faker->randomFloat(2, 100, 50000),
            'status' => $this->faker->randomElement(['submitted', 'under_review', 'approved', 'rejected', 'paid', 'disputed']),
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'submitted_at' => $submittedAt,
            'approved_at' => null,
            'approved_by' => null,
            'rejection_reason' => null,
            'notes' => $this->faker->optional()->paragraph()
        ];
    }
}
