<?php

namespace Database\Seeders;

use App\Models\VendorCategory;
use Illuminate\Database\Seeder;

class VendorCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'IT Services',
                'slug' => 'it-services',
                'description' => 'Software development, IT consulting, system integration',
                'status' => 'active',
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Office Supplies',
                'slug' => 'office-supplies',
                'description' => 'Stationery, furniture, office equipment',
                'status' => 'active',
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Professional Services',
                'slug' => 'professional-services',
                'description' => 'Legal, accounting, consulting services',
                'status' => 'active',
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Maintenance',
                'slug' => 'maintenance',
                'description' => 'Building maintenance, cleaning, repairs',
                'status' => 'active',
                'is_featured' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Marketing',
                'slug' => 'marketing',
                'description' => 'Advertising, digital marketing, branding',
                'status' => 'active',
                'is_featured' => false,
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            VendorCategory::create($category);
        }
    }
}