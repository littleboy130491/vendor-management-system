<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'reviewer_id', 'rating_quality', 'rating_timeliness', 'rating_communication', 'comments'
    ];

    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewer_id'); }
}