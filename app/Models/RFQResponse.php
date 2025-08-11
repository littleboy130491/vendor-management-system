<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RFQResponse extends Model
{
    use HasFactory;

    protected $table = 'rfq_responses';

    protected $fillable = [
        'rfq_id', 'vendor_id', 'quoted_amount', 'delivery_time_days',
        'status', 'technical_score', 'commercial_score', 'total_score',
        'notes', 'submitted_at',
    ];

    protected $casts = [
        'quoted_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'technical_score' => 'float',
        'commercial_score' => 'float',
        'total_score' => 'float',
    ];

    public function rfq()
    {
        return $this->belongsTo(RFQ::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function evaluations()
    {
        return $this->hasMany(RFQEvaluation::class, 'rfq_response_id');
    }
}