<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWarning;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorWarning>
 */
class VendorWarningFactory extends Factory
{
    protected $model = VendorWarning::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'issued_by' => User::factory(),
            'type' => $this->faker->randomElement(['Late Delivery','Quality Issue','Communication']),
            'details' => $this->faker->sentence(),
            'resolved_at' => null,
        ];
    }
}