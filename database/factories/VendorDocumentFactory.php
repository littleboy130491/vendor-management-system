<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VendorDocument>
 */
class VendorDocumentFactory extends Factory
{
    protected $model = VendorDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = $this->faker->unique()->words(2, true);
        $extension = $this->faker->randomElement(['pdf','docx','xlsx','jpg']);
        $mimeMap = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
        ];

        return [
            'vendor_id' => Vendor::factory(),
            'collection_name' => 'vendor_documents',
            'file_name' => Str::slug($filename) . '.' . $extension,
            'file_size' => $this->faker->numberBetween(10_000, 5_000_000),
            'mime_type' => $mimeMap[$extension],
            'custom_properties' => [
                'document_type' => $this->faker->randomElement(['tax_certificate','business_license','insurance','other']),
                'notes' => $this->faker->sentence(),
            ],
            'expires_at' => $this->faker->optional(0.4)->dateTimeBetween('+1 month', '+2 years'),
        ];
    }
}
