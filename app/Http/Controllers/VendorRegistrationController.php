<?php

namespace App\Http\Controllers;

use App\Http\Requests\VendorRegistrationRequest;
use App\Models\VendorCategory;
use App\Services\VendorOnboardingService;
use Illuminate\Http\Request;

class VendorRegistrationController extends Controller
{
    public function __construct(
        private VendorOnboardingService $vendorOnboardingService
    ) {}

    public function create()
    {
        $categories = VendorCategory::where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
            
        return view('vendor-registration.create', compact('categories'));
    }

    public function store(VendorRegistrationRequest $request)
    {
        $vendor = $this->vendorOnboardingService->createVendorFromRegistration(
            $request->validated()
        );

        return redirect()
            ->route('vendor-registration.success')
            ->with('message', 'Registration submitted successfully! We will review your application and contact you soon.');
    }

    public function success()
    {
        return view('vendor-registration.success');
    }
}