<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\POItem>
 */
class POItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 100);
        $unitPrice = $this->faker->randomFloat(2, 1, 500);
        $lineTotal = $quantity * $unitPrice;
        
        return [
            'purchase_order_id' => \App\Models\PurchaseOrder::factory(),
            'item_name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'unit_of_measure' => $this->faker->randomElement(['each', 'box', 'case', 'dozen', 'pound', 'kilogram', 'meter', 'foot'])
        ];
    }
}
