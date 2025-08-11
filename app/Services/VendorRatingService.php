<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\VendorReview;
use Illuminate\Support\Collection;

class VendorRatingService
{
    public function addReview(Vendor $vendor, User $reviewer, array $ratings): VendorReview
    {
        $review = VendorReview::create([
            'vendor_id' => $vendor->id,
            'reviewer_id' => $reviewer->id,
            'rating_quality' => $ratings['quality'],
            'rating_timeliness' => $ratings['timeliness'],
            'rating_communication' => $ratings['communication'],
            'comments' => $ratings['comments'] ?? null,
        ]);

        $vendor->updateRatingAverage();

        return $review;
    }

    public function getVendorRankings(VendorCategory $category = null): Collection
    {
        return Vendor::query()
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->where('status', 'active')
            ->orderByDesc('rating_average')
            ->with(['category', 'reviews'])
            ->get();
    }
}