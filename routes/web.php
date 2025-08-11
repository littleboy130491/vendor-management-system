<?php

use App\Http\Controllers\VendorRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Vendor Registration Routes
Route::get('/vendor-registration', [VendorRegistrationController::class, 'create'])
    ->name('vendor-registration.create');
Route::post('/vendor-registration', [VendorRegistrationController::class, 'store'])
    ->name('vendor-registration.store');
Route::get('/vendor-registration/success', [VendorRegistrationController::class, 'success'])
    ->name('vendor-registration.success');
