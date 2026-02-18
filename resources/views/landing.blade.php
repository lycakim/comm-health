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
        <style>
            /* 3D Coverflow carousel – scoped, no Tailwind */
            .coverflow-section {
                background: linear-gradient(160deg, #f0faf4, #e8f5ee, #f5fbf7);
                padding: 3rem 1rem;
            }
            .coverflow-heading {
                text-align: center;
                margin-bottom: 2rem;
            }
            .coverflow-heading h2 {
                font-size: 1.875rem;
                font-weight: 700;
                margin: 0 0 0.5rem 0;
            }
            .coverflow-heading p {
                font-size: 1rem;
                margin: 0;
            }
            .coverflow-stage {
                perspective: 1000px;
                height: 460px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto;
            }
            .coverflow-track {
                position: relative;
                width: 100%;
                height: 100%;
                transform-style: preserve-3d;
            }
            .coverflow-card {
                position: absolute;
                left: 50%;
                top: 50%;
                width: 300px;
                height: 400px;
                border-radius: 16px;
                overflow: hidden;
                cursor: pointer;
                transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                            opacity 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                            box-shadow 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                backface-visibility: hidden;
            }
            .coverflow-card img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            .coverflow-card.is-active {
                box-shadow: 0 20px 40px rgba(26, 122, 74, 0.35);
            }
            .coverflow-card.is-active::after {
                content: '';
                position: absolute;
                inset: 0;
                border: 2.5px solid rgba(26, 122, 74, 0.5);
                border-radius: 16px;
                pointer-events: none;
            }
            .coverflow-card .card-reflection {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 40%;
                background: linear-gradient(to top, rgba(255,255,255,0.5), transparent);
                pointer-events: none;
            }
            .coverflow-controls {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 1.5rem;
                margin-top: 2rem;
            }
            .coverflow-btn {
                width: 44px;
                height: 44px;
                border-radius: 50%;
                border: 2px solid #1a7a4a;
                background: transparent;
                color: #1a7a4a;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.2s, color 0.2s;
            }
            .coverflow-btn:hover {
                background: #1a7a4a;
                color: #fff;
            }
            .coverflow-dots {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .coverflow-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #c4dece;
                border: none;
                padding: 0;
                cursor: pointer;
                transition: width 0.3s, border-radius 0.3s, background 0.3s;
            }
            .coverflow-dot.is-active {
                width: 24px;
                border-radius: 4px;
                background: #1a7a4a;
            }
            @media (max-width: 640px) {
                .coverflow-stage {
                    height: 320px;
                }
                .coverflow-card {
                    width: 220px;
                    height: 293px;
                }
                .coverflow-card.is-active::after {
                    border-radius: 12px;
                }
            }
        </style>
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
            <div class="bg-white py-8">
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
                        <a href="{{ route('login') }}" class="px-6 py-3 border border-emerald-600 text-emerald-600 font-medium rounded-md hover:bg-emerald-50 transition-colors">
                            MHO Portal
                        </a>
                        <a href="{{ route('login') }}" class="px-6 py-3 border border-emerald-600 text-emerald-600 font-medium rounded-md hover:bg-emerald-50 transition-colors">
                            Health Worker Portal
                        </a>
                    </div>
                </div>
            </div>

            @if(isset($photos) && count($photos) > 0)
            <section class="coverflow-section" id="coverflow-section" data-count="{{ count($photos) }}">
                <div class="coverflow-heading">
                    <h2 class="text-emerald-600">Our Community Health Activities</h2>
                    <p class="text-gray-600">Capturing moments from our health programs and community outreach</p>
                </div>
                <div class="coverflow-stage">
                    <div class="coverflow-track" style="transform-style: preserve-3d;">
                        @foreach($photos as $index => $photo)
                        <div class="coverflow-card" data-index="{{ $index }}" role="button" tabindex="0">
                            <img src="{{ $photo }}" alt="Community Health Activity {{ $index + 1 }}" loading="lazy">
                            <div class="card-reflection"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="coverflow-controls">
                    <button type="button" class="coverflow-btn coverflow-btn-prev" aria-label="Previous">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <div class="coverflow-dots">
                        @foreach($photos as $index => $photo)
                        <button type="button" class="coverflow-dot" data-index="{{ $index }}" aria-label="Go to slide {{ $index + 1 }}"></button>
                        @endforeach
                    </div>
                    <button type="button" class="coverflow-btn coverflow-btn-next" aria-label="Next">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>
            </section>
            @endif

            <!-- Features Section -->
            <div class="py-12 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-center gap-8">
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
                            <a href="{{ route('login') }}" class="w-full block text-center px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 transition-colors">
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
                            <a href="{{ route('login') }}" class="w-full block text-center px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 transition-colors">
                                Access Health Worker Portal
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mission Section -->
            <div class="py-12 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold text-emerald-600 mb-4">Improving Healthcare Delivery</h2>
                    <p class="max-w-3xl mx-auto text-gray-600">
                        CommHealth aims to enhance patient profiling, scheduling, referrals, and reporting, 
                        enabling better coordination between barangay health centers and the Municipal Health OFFICE.
                    </p>
                </div>
            </div>

            <div class="py-12 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold text-emerald-600 mb-4">Disclaimer</h2>
                    <p class="max-w-3xl mx-auto text-gray-600">
                        This system is for authorized health workers only. All patient data is confidential. Unauthorized access is prohibited.
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
                            <img src="{{ asset('comm-health-icon.png') }}" alt="Comm Health Logo" class="h-12 w-auto">
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
    @if(isset($photos) && count($photos) > 0)
    <script>
    (function() {
        var section = document.getElementById('coverflow-section');
        if (!section) return;

        var track = section.querySelector('.coverflow-track');
        var cards = section.querySelectorAll('.coverflow-card');
        var dots = section.querySelectorAll('.coverflow-dot');
        var btnPrev = section.querySelector('.coverflow-btn-prev');
        var btnNext = section.querySelector('.coverflow-btn-next');
        var count = cards.length;
        var active = Math.min(2, count - 1);

        function getStyle(index) {
            var offset = index - active;
            var absOffset = Math.abs(offset);
            if (absOffset > 2) {
                return { display: 'none' };
            }
            var translateZ = [0, -140, -260][absOffset] + 'px';
            var scale = [1, 0.82, 0.65][absOffset];
            var opacity = [1, 0.75, 0.45][absOffset];
            var zIndex = [10, 5, 1][absOffset];
            var tx = offset * 280;
            return {
                display: 'block',
                transform: 'translate(-50%, -50%) translateX(' + tx + 'px) translateZ(' + translateZ + ') rotateY(' + (offset * -28) + 'deg) scale(' + scale + ')',
                opacity: opacity,
                zIndex: zIndex
            };
        }

        function applyStyles() {
            for (var i = 0; i < cards.length; i++) {
                var style = getStyle(i);
                for (var prop in style) {
                    cards[i].style[prop] = style[prop];
                }
                cards[i].classList.toggle('is-active', i === active);
            }
            for (var j = 0; j < dots.length; j++) {
                dots[j].classList.toggle('is-active', j === active);
            }
        }

        function goTo(index) {
            active = (index + count) % count;
            applyStyles();
        }

        function goNext() {
            goTo(active + 1);
        }

        function goPrev() {
            goTo(active - 1);
        }

        applyStyles();

        btnPrev.addEventListener('click', goPrev);
        btnNext.addEventListener('click', goNext);

        cards.forEach(function(card, i) {
            card.addEventListener('click', function() { goTo(i); });
        });
        dots.forEach(function(dot, i) {
            dot.addEventListener('click', function() { goTo(i); });
        });

        setInterval(goNext, 4500);
    })();
    </script>
    @endif
    @livewireScripts
    @stack('scripts')
</body>
</html>