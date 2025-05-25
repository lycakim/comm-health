<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'CommHealth') }}</title>
        <!-- Add Inter font -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
<body class="antialiased bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <div class="flex items-center justify-start">
                    <img src="{{ asset('comm-health-icon.png') }}" alt="Comm Health Logo" class="h-8 w-auto">
                    <span class="text-2xl font-bold text-emerald-600 ml-2">CommHealth</span>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-200 rounded-md hover:text-gray-900">Login</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-md hover:bg-gray-700">Register</a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow">
            <!-- Hero Section -->
            <div class="bg-white py-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h1 class="text-3xl font-extrabold text-emerald-600 sm:text-4xl">
                            Municipal Health Office Management System
                        </h1>
                        <p class="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                            A comprehensive health information system for {{ config('app.location', 'Your Municipality') }}
                        </p>
                    </div>

                    <!-- Portal Buttons -->
                    <div class="mt-10 flex flex-wrap justify-center gap-4">
                        <a href="#" class="px-6 py-3 border border-emerald-600 text-emerald-600 font-medium rounded-md hover:bg-emerald-50 transition-colors">
                            MHO Portal
                        </a>
                        <a href="#" class="px-6 py-3 border border-emerald-600 text-emerald-600 font-medium rounded-md hover:bg-emerald-50 transition-colors">
                            Health Worker Portal
                        </a>
                        <a href="#" class="px-6 py-3 border border-emerald-600 text-emerald-600 font-medium rounded-md hover:bg-emerald-50 transition-colors">
                            Resident Portal
                        </a>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="py-12 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                        <!-- MHO Administrators -->
                        <div class="bg-white p-6 rounded-lg shadow-sm">
                            <h2 class="text-xl font-semibold text-emerald-600 mb-4">For MHO Administrators</h2>
                            <p class="text-gray-600 mb-4">Comprehensive oversight of health services</p>
                            <ul class="space-y-2 mb-6">
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">Manage user accounts</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">Monitor patient records</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">Create health program schedules</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">Access data visualization dashboard</span>
                                </li>
                            </ul>
                            <a href="#" class="w-full block text-center px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 transition-colors">
                                Access MHO Portal
                            </a>
                        </div>

                        <!-- Health Workers -->
                        <div class="bg-white p-6 rounded-lg shadow-sm">
                            <h2 class="text-xl font-semibold text-emerald-600 mb-4">For Midwives & BHWs</h2>
                            <p class="text-gray-600 mb-4">Efficient patient management tools</p>
                            <ul class="space-y-2 mb-6">
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">Patient profiling system</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">Consultation records</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">Referral management</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">Report generation</span>
                                </li>
                            </ul>
                            <a href="#" class="w-full block text-center px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 transition-colors">
                                Access Health Worker Portal
                            </a>
                        </div>

                        <!-- Residents -->
                        <div class="bg-white p-6 rounded-lg shadow-sm">
                            <h2 class="text-xl font-semibold text-emerald-600 mb-4">For Residents</h2>
                            <p class="text-gray-600 mb-4">Your health information at your fingertips</p>
                            <ul class="space-y-2 mb-6">
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">View personal health records</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">Check upcoming health programs</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">Receive health reminders</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600">Track consultation history</span>
                                </li>
                            </ul>
                            <a href="#" class="w-full block text-center px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 transition-colors">
                                Access Resident Portal
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mission Section -->
            <div class="py-12 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold text-emerald-600 mb-4">Improving Healthcare Delivery</h2>
                    <p class="max-w-3xl mx-auto text-gray-600">
                        CommHealth aims to enhance patient profiling, scheduling, referrals, and reporting, 
                        enabling better coordination between barangay health centers and the Municipal Health OFFICE.
                    </p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-emerald-700 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="md:flex md:justify-between items-center">
                    <div class="mb-6 md:mb-0">
                        <div class="flex items-center">
                            <img src="{{ asset('comm-health-logo-white.png') }}" alt="Comm Health Logo" class="h-8 w-auto">
                        </div>
                        <p class="mt-2 text-sm text-emerald-100">
                            Municipal Health Office of {{ config('app.location', 'Your Municipality') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-emerald-100">
                            © {{ date('Y') }} CommHealth. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>