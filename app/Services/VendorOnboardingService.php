<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vendor;
use App\Notifications\VendorRegistrationAdminNotification;
use App\Notifications\VendorRegistrationReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class VendorOnboardingService
{
    public function createVendor(array $data): Vendor
    {
        return DB::transaction(function () use ($data) {
            $vendor = Vendor::create($data);

            // Here we could notify admins, log activity, etc.

            return $vendor;
        });
    }

    public function approveVendor(Vendor $vendor, User $approver): bool
    {
        return DB::transaction(function () use ($vendor, $approver) {
            $vendor->update(['status' => 'active']);

            if (!$vendor->user_id) {
                $user = $this->createVendorUser($vendor);
                $vendor->update(['user_id' => $user->id]);
            }

            // send notifications, log, etc.

            return true;
        });
    }

    private function createVendorUser(Vendor $vendor): User
    {
        $user = User::create([
            'name' => $vendor->contact_name,
            'email' => $vendor->contact_email,
            'password' => Hash::make(Str::random(12)),
        ]);

        $user->assignRole('vendor');

        return $user;
    }

    public function createVendorFromRegistration(array $data): Vendor
    {
        return DB::transaction(function () use ($data) {
            // Ensure status is pending for self-registrations
            $data['status'] = 'pending';
            
            $vendor = Vendor::create($data);

            // Send confirmation email to vendor
            Notification::route('mail', $vendor->contact_email)
                ->notify(new VendorRegistrationReceived($vendor));

            // Notify admins of new vendor registration
            $admins = User::role('super_admin')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new VendorRegistrationAdminNotification($vendor));
            }

            // Log activity (if activity logging is set up)
            try {
                if (function_exists('activity')) {
                    activity('vendor_self_registered')
                        ->performedOn($vendor)
                        ->log('Vendor self-registration: ' . $vendor->company_name);
                }
            } catch (\Exception $e) {
                // Activity logging not available, continue without it
            }

            return $vendor;
        });
    }
}