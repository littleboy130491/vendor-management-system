<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'collection_name',
        'file_name',
        'file_size',
        'mime_type',
        'custom_properties',
        'expires_at',
    ];

    protected $casts = [
        'custom_properties' => 'array',
        'expires_at' => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
