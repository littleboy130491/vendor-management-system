<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        $company = $this->faker->unique()->company();
        return [
            'user_id' => null,
            'company_name' => $company,
            // Let Spatie Sluggable generate the slug based on company_name
            'category_id' => VendorCategory::factory(),
            'contact_name' => $this->faker->name(),
            'contact_email' => $this->faker->unique()->safeEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'tax_id' => 'TX-'.$this->faker->unique()->numerify('####'),
            'status' => 'pending',
            'rating_average' => 0.00,
            'metadata' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn() => ['status' => 'active']);
    }
}