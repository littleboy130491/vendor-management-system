<?php

namespace Database\Factories;

use App\Models\RFQ;
use App\Models\RFQResponse;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RFQResponse>
 */
class RFQResponseFactory extends Factory
{
    protected $model = RFQResponse::class;

    public function definition(): array
    {
        return [
            'rfq_id' => RFQ::factory(),
            'vendor_id' => Vendor::factory(),
            'quoted_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'delivery_time_days' => $this->faker->numberBetween(7, 90),
            'status' => 'submitted',
            'technical_score' => null,
            'commercial_score' => null,
            'total_score' => null,
            'notes' => $this->faker->sentence(),
            'submitted_at' => now(),
        ];
    }
}