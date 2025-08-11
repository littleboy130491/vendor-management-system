<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendor Registration - {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div class="w-full sm:max-w-2xl mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <div class="mb-6 text-center">
                <h1 class="text-3xl font-bold text-gray-900">Vendor Registration</h1>
                <p class="mt-2 text-sm text-gray-600">Join our vendor network by completing this registration form</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
                    <strong class="font-bold">Please correct the following errors:</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('vendor-registration.store') }}">
                @csrf

                <!-- Company Information -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Company Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name *</label>
                            <input id="company_name" type="text" name="company_name" value="{{ old('company_name') }}" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                   required>
                        </div>

                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700">Business Category *</label>
                            <select id="category_id" name="category_id" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    required>
                                <option value="">Select a category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="tax_id" class="block text-sm font-medium text-gray-700">Tax ID</label>
                            <input id="tax_id" type="text" name="tax_id" value="{{ old('tax_id') }}" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="address" class="block text-sm font-medium text-gray-700">Business Address</label>
                        <textarea id="address" name="address" rows="3" 
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('address') }}</textarea>
                    </div>

                    <div class="mt-4">
                        <label for="company_description" class="block text-sm font-medium text-gray-700">Company Description</label>
                        <textarea id="company_description" name="company_description" rows="4" 
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                  placeholder="Tell us about your company, services, and expertise...">{{ old('company_description') }}</textarea>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Contact Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="contact_name" class="block text-sm font-medium text-gray-700">Contact Person Name *</label>
                            <input id="contact_name" type="text" name="contact_name" value="{{ old('contact_name') }}" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                   required>
                        </div>

                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-gray-700">Contact Email *</label>
                            <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email') }}" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                   required>
                        </div>

                        <div>
                            <label for="contact_phone" class="block text-sm font-medium text-gray-700">Contact Phone</label>
                            <input id="contact_phone" type="tel" name="contact_phone" value="{{ old('contact_phone') }}" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="mb-6">
                    <div class="flex items-center">
                        <input id="terms_accepted" type="checkbox" name="terms_accepted" value="1" 
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                               {{ old('terms_accepted') ? 'checked' : '' }} required>
                        <label for="terms_accepted" class="ml-2 block text-sm text-gray-900">
                            I accept the <a href="#" class="text-indigo-600 hover:text-indigo-500">Terms and Conditions</a> 
                            and <a href="#" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</a> *
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <a href="{{ url('/') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:outline-none focus:border-gray-500 focus:ring focus:ring-gray-200 transition ease-in-out duration-150">
                        Back to Home
                    </a>

                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                        Submit Registration
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>