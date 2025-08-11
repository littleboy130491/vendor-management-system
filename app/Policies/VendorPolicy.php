<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Access\HandlesAuthorization;

class VendorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_vendors');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        if ($user->can('view_vendors')) return true;
        return $user->id === $vendor->user_id;
    }

    public function create(User $user): bool
    {
        return true;
        return $user->can('manage_vendors');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        if ($user->can('manage_vendors')) return true;
        return $user->id === $vendor->user_id && $vendor->status !== 'blacklisted';
    }

    public function approve(User $user, Vendor $vendor): bool
    {
        return $user->can('approve_vendors') && $vendor->status === 'pending';
    }

    public function suspend(User $user, Vendor $vendor): bool
    {
        return $user->can('manage_vendors') && $vendor->status === 'active';
    }
}