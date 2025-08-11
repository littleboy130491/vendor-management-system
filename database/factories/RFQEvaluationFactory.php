<?php

namespace Database\Factories;

use App\Models\RFQEvaluation;
use App\Models\RFQResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RFQEvaluation>
 */
class RFQEvaluationFactory extends Factory
{
    protected $model = RFQEvaluation::class;

    public function definition(): array
    {
        return [
            'rfq_response_id' => RFQResponse::factory(),
            'evaluator_id' => User::factory(),
            'criteria_scores' => ['technical' => 80, 'commercial' => 90],
            'comments' => $this->faker->sentence(),
            'total_score' => 85.0,
        ];
    }
}