<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration Success - {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div class="w-full sm:max-w-lg mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <div class="text-center">
                <!-- Success Icon -->
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-4">Registration Successful!</h1>
                
                @if(session('message'))
                    <div class="mb-6 text-sm text-gray-600">
                        {{ session('message') }}
                    </div>
                @endif

                <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
                    <h2 class="font-semibold text-green-800 mb-2">What happens next?</h2>
                    <ul class="text-sm text-green-700 text-left space-y-2">
                        <li class="flex items-start">
                            <span class="flex-shrink-0 h-5 w-5 text-green-500 mr-2">•</span>
                            Our team will review your registration within 1-2 business days
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 h-5 w-5 text-green-500 mr-2">•</span>
                            You will receive an email confirmation once approved
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 h-5 w-5 text-green-500 mr-2">•</span>
                            If approved, you'll get login credentials to access our vendor portal
                        </li>
                        <li class="flex items-start">
                            <span class="flex-shrink-0 h-5 w-5 text-green-500 mr-2">•</span>
                            You can then participate in RFQs and manage your vendor profile
                        </li>
                    </ul>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                    <h3 class="font-medium text-blue-800 mb-2">Need Help?</h3>
                    <p class="text-sm text-blue-700">
                        If you have any questions about your registration or our vendor program, 
                        please contact our procurement team at 
                        <a href="mailto:vendors@company.com" class="font-medium underline">vendors@company.com</a>
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ url('/') }}" 
                       class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 transition ease-in-out duration-150">
                        Return to Home
                    </a>
                    
                    <a href="{{ route('vendor-registration.create') }}" 
                       class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 transition ease-in-out duration-150">
                        Register Another Vendor
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>