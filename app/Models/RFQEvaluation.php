<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RFQEvaluation extends Model
{
    use HasFactory;

    protected $table = 'rfq_evaluations';

    protected $fillable = [
        'rfq_response_id', 'evaluator_id', 'criteria_scores', 'comments', 'total_score',
    ];

    protected $casts = [
        'criteria_scores' => 'array',
        'total_score' => 'float',
    ];

    public function response()
    {
        return $this->belongsTo(RFQResponse::class, 'rfq_response_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}