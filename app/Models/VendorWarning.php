<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorWarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'issued_by', 'type', 'details', 'resolved_at'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function issuer() { return $this->belongsTo(User::class, 'issued_by'); }
}