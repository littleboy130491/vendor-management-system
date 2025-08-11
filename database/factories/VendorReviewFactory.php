<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorReview>
 */
class VendorReviewFactory extends Factory
{
    protected $model = VendorReview::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'reviewer_id' => User::factory(),
            'rating_quality' => $this->faker->numberBetween(1,5),
            'rating_timeliness' => $this->faker->numberBetween(1,5),
            'rating_communication' => $this->faker->numberBetween(1,5),
            'comments' => $this->faker->optional()->sentence(),
        ];
    }
}