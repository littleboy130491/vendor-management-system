<?php

namespace Database\Factories;

use App\Models\RFQ;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RFQ>
 */
class RFQFactory extends Factory
{
    protected $model = RFQ::class;

    public function definition(): array
    {
        $title = 'RFQ ' . Str::title($this->faker->words(3, true));
        $start = now()->subDay();
        $end = now()->addDays(7);
        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(6),
            'description' => $this->faker->paragraph(),
            'status' => 'draft',
            'created_by' => User::factory(),
            'starts_at' => $start,
            'ends_at' => $end,
            'evaluation_criteria' => [
                'weights' => ['technical' => 0.5, 'commercial' => 0.5],
            ],
            'scope' => ['notes' => 'See attachment'],
            'currency' => 'USD',
            'budget' => 100000,
            'published_at' => null,
        ];
    }

    public function published(): self
    {
        return $this->state(function (array $attrs) {
            return [
                'status' => 'published',
                'published_at' => now(),
            ];
        });
    }
}