<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SkillSync - Training & Appointment Management</title>
    @vite('resources/css/app.css')
</head>
<body class="antialiased bg-gray-50">
    <!-- Navigation -->
    <header class="bg-white shadow">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600 mr-2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                <span class="text-xl font-bold text-blue-600">SkillSync</span>
            </div>
            <div class="space-x-2">
                <a href="{{ route('login') }}" class="px-4 py-1 border border-gray-300 rounded text-sm font-medium text-gray-700 hover:bg-gray-50">Login</a>
                <a href="{{ route('register') }}" class="px-4 py-1 bg-blue-600 rounded text-sm font-medium text-white hover:bg-blue-700">Register</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-blue-600 text-white py-12">
        <div class="container mx-auto px-4">
            <h1 class="text-2xl md:text-3xl font-bold mb-2">Online Training & Appointment Management System</h1>
            <p class="mb-6">Enhance your skills with TESDA-accredited courses at Panabo City Skills Training and Assessment Center</p>
            <a href="{{ route('register') }}" class="inline-block px-6 py-2 bg-white text-blue-600 rounded-md font-medium hover:bg-gray-100 transition">Register Now</a>
        </div>
    </section>

    <!-- Training Programs -->
    <section class="py-16 container mx-auto px-4">
        <h2 class="text-2xl font-bold text-center mb-12">Our Training Programs</h2>
        
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Technical Vocational Programs -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold mb-4">Technical Vocational Programs</h3>
                <p class="text-sm text-gray-600 mb-4">TESDA-accredited technical courses</p>
                
                <ul class="space-y-3 mb-6">
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Welding</span>
                    </li>
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Driving</span>
                    </li>
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Housekeeping</span>
                    </li>
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Bookkeeping</span>
                    </li>
                </ul>
                
                <a href="#" class="w-full block text-center py-2 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700 transition">View Programs</a>
            </div>
            
            <!-- Livelihood Programs -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold mb-4">Livelihood Programs</h3>
                <p class="text-sm text-gray-600 mb-4">Self-employment and entrepreneurship courses</p>
                
                <ul class="space-y-3 mb-6">
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Bread and Pastry Production</span>
                    </li>
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Beauty Care</span>
                    </li>
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Dressmaking</span>
                    </li>
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Hydroponics Agriculture</span>
                    </li>
                </ul>
                
                <a href="#" class="w-full block text-center py-2 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700 transition">View Programs</a>
            </div>
            
            <!-- Assessment Services -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold mb-4">Assessment Services</h3>
                <p class="text-sm text-gray-600 mb-4">Get certified for your skills</p>
                
                <ul class="space-y-3 mb-6">
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Skills Assessment</span>
                    </li>
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Certification</span>
                    </li>
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Industry Linkages</span>
                    </li>
                    <li class="flex items-center">
                        <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <span>Employment Assistance</span>
                    </li>
                </ul>
                
                <a href="#" class="w-full block text-center py-2 bg-blue-600 text-white rounded text-sm font-medium hover:bg-blue-700 transition">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold text-center mb-12">Our Impact</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-6 rounded-lg shadow-sm text-center">
                    <div class="text-2xl font-bold text-blue-600 mb-1">25+</div>
                    <div class="text-sm text-gray-600">Trainers Per Batch</div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm text-center">
                    <div class="text-2xl font-bold text-blue-600 mb-1">150+</div>
                    <div class="text-sm text-gray-600">Quarterly Placements</div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm text-center">
                    <div class="text-2xl font-bold text-blue-600 mb-1">10+</div>
                    <div class="text-sm text-gray-600">Training Programs</div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm text-center">
                    <div class="text-2xl font-bold text-blue-600 mb-1">2019</div>
                    <div class="text-sm text-gray-600">Established</div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-16 container mx-auto px-4">
        <h2 class="text-2xl font-bold text-center mb-12">How It Works</h2>
        
        <div class="grid md:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <h3 class="font-semibold mb-1">1. Registration</h3>
                <p class="text-sm text-gray-600">Register online and submit your requirements</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <h3 class="font-semibold mb-1">2. Induction</h3>
                <p class="text-sm text-gray-600">Attend the training induction program</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3 class="font-semibold mb-1">3. Training</h3>
                <p class="text-sm text-gray-600">Complete your selected training program</p>
            </div>
            
            <div class="text-center">
                <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <h3 class="font-semibold mb-1">4. Certification</h3>
                <p class="text-sm text-gray-600">Receive your certificate and assessment</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-blue-800 text-white py-8">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Center Info -->
                <div>
                    <h3 class="font-semibold text-lg mb-4">Panabo Skills Training Center</h3>
                    <p class="text-blue-200 mb-2">Barangay Datu Abdul Dadia, Panabo City</p>
                    <p class="text-blue-200">Providing quality technical and vocational education since 2019</p>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="font-semibold text-lg mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-blue-200 hover:text-white transition">Programs</a></li>
                        <li><a href="#" class="text-blue-200 hover:text-white transition">Register</a></li>
                        <li><a href="#" class="text-blue-200 hover:text-white transition">Login</a></li>
                        <li><a href="#" class="text-blue-200 hover:text-white transition">Contact Us</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h3 class="font-semibold text-lg mb-4">Contact Information</h3>
                    <p class="flex items-center text-blue-200 mb-2">
                        <span class="mr-2">Email:</span>
                        <a href="mailto:info@panaboskills.gov.ph" class="hover:text-white transition">info@panaboskills.gov.ph</a>
                    </p>
                    <p class="flex items-center text-blue-200">
                        <span class="mr-2">Phone:</span>
                        <a href="tel:+123456789" class="hover:text-white transition">(123) 456-7890</a>
                    </p>
                </div>
            </div>
            
            <div class="border-t border-blue-700 mt-8 pt-6 text-center text-blue-200 text-sm">
                &copy; 2023 Panabo City Skills Training and Assessment Center. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>