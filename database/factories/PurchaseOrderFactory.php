<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issuedDate = $this->faker->dateTimeBetween('-1 month', 'now');
        $expectedDeliveryDate = $this->faker->dateTimeBetween($issuedDate, '+1 month');
        
        return [
            'po_number' => 'PO-' . now()->year . '-' . str_pad($this->faker->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'contract_id' => null,
            'vendor_id' => \App\Models\Vendor::factory(),
            'issued_by' => \App\Models\User::factory(),
            'total_amount' => $this->faker->randomFloat(2, 100, 50000),
            'status' => $this->faker->randomElement(['draft', 'approved', 'sent', 'acknowledged', 'delivered', 'completed', 'cancelled']),
            'issued_date' => $issuedDate,
            'expected_delivery_date' => $expectedDeliveryDate,
            'actual_delivery_date' => null,
            'notes' => $this->faker->optional()->paragraph(),
            'delivery_address' => [
                'street' => $this->faker->streetAddress,
                'city' => $this->faker->city,
                'state' => $this->faker->stateAbbr,
                'zip' => $this->faker->postcode,
                'country' => 'US'
            ]
        ];
    }
}
