<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Vendor extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasSlug;

    protected $fillable = [
        'user_id', 'company_name', 'slug', 'category_id',
        'contact_name', 'contact_email', 'contact_phone',
        'address', 'company_description', 'tax_id', 'status', 'rating_average', 'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'rating_average' => 'decimal:2'
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('company_name')
            ->saveSlugsTo('slug');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('vendor_documents')->useDisk('public');
    }

    // Relationships
    public function user() { return $this->belongsTo(User::class); }
    public function category() { return $this->belongsTo(VendorCategory::class, 'category_id'); }
    public function reviews() { return $this->hasMany(VendorReview::class); }
    public function warnings() { return $this->hasMany(VendorWarning::class); }
    public function documents() { return $this->hasMany(VendorDocument::class); }

    // Scopes
    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopePending($query) { return $query->where('status', 'pending'); }

    public function updateRatingAverage(): void
    {
        $average = $this->reviews()
            ->selectRaw('AVG((rating_quality + rating_timeliness + rating_communication) / 3) as avg_rating')
            ->value('avg_rating') ?? 0;

        $this->update(['rating_average' => round((float)$average, 2)]);
    }
}