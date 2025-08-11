<?php

namespace Database\Factories;

use App\Models\VendorCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorCategory>
 */
class VendorCategoryFactory extends Factory
{
    protected $model = VendorCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(['IT Services','Office Supplies','Professional Services','Maintenance','Marketing']).' '.$this->faker->word();
        
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'is_featured' => $this->faker->boolean(20), // 20% chance of being featured
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}