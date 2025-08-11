<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RFQ extends Model
{
    use HasFactory;

    protected $table = 'rfqs';

    protected $fillable = [
        'title', 'slug', 'description', 'status', 'created_by',
        'starts_at', 'ends_at', 'evaluation_criteria', 'scope',
        'currency', 'budget', 'published_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'published_at' => 'datetime',
        'evaluation_criteria' => 'array',
        'scope' => 'array',
        'budget' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function (RFQ $rfq) {
            if (empty($rfq->slug)) {
                $rfq->slug = Str::slug($rfq->title) . '-' . Str::random(6);
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'rfq_vendors', 'rfq_id', 'vendor_id')
            ->withPivot(['status', 'invited_at', 'responded_at', 'awarded_at'])
            ->withTimestamps();
    }

    public function responses()
    {
        return $this->hasMany(RFQResponse::class);
    }

    public function respondedVendors()
    {
        return $this->belongsToMany(Vendor::class, 'rfq_vendors', 'rfq_id', 'vendor_id')
            ->withPivot(['status', 'responded_at'])
            ->wherePivot('status', 'responded');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function canReceiveResponses(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }
        if ($this->ends_at && now()->greaterThan($this->ends_at)) {
            return false;
        }
        return true;
    }
}