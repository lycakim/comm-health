<x-filament-panels::page.simple>
    <div class="flex flex-col items-center justify-center space-y-2 text-center" style="margin-top: -35px; color: #087757;">
         <h2 class="text-2xl font-bold">
            CommHealth
        </h2>
    </div>
    
    @if ($showOtpForm)
        {{-- OTP Verification Section --}}
        <div class="flex flex-col items-center justify-center space-y-2 text-center" style="margin-top: -25px;">
            <h2 class="text-2xl font-bold">
                Enter OTP Code
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                We've sent a 6-digit code to your email address.
            </p>
        </div>

        <x-filament-panels::form wire:submit="authenticate">
            {{ $this->form }}

            <x-filament::button type="submit" class="w-full bg-green-600 hover:bg-green-700">
                Verify OTP
            </x-filament::button>
        </x-filament-panels::form>

        {{-- OTP Actions --}}
        <div class="text-center mt-6 space-y-2">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Didn't receive the code? 
                <button 
                    type="button" 
                    wire:click="resendOtp"
                    class="text-green-600 hover:text-green-700 font-medium"
                >
                    Resend OTP
                </button>
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <button 
                    type="button" 
                    wire:click="$set('showOtpForm', false)"
                    class="text-green-600 hover:text-green-700 font-medium"
                >
                    ← Back to Login
                </button>
            </p>
        </div>
    @else
        {{-- Regular Login Section --}}
        <div class="flex flex-col items-center justify-center space-y-2 text-center" style="margin-top: -25px;">
            <h2 class="text-2xl font-bold">
                Sign in
            </h2>
        </div>

        <x-filament-panels::form wire:submit="authenticate">
            {{ $this->form }}

            <x-filament::button type="submit" form="authenticate" class="w-full bg-green-600 hover:bg-green-700">
                {{ __('filament-panels::pages/auth/login.form.actions.authenticate.label') }}
            </x-filament::button>
        </x-filament-panels::form>

        <div class="text-center mt-6 space-y-2">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Doesn't have an account? 
                <a href="{{ route('register') }}" class="text-green-600 hover:text-green-700 font-medium">
                    Sign Up
                </a>
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <a href="/" class="text-green-600 hover:text-green-700 font-medium">
                    Back to Home
                </a>
            </p>
        </div>
    @endif

    <style>
        h1.text-2xl.font-bold:first-of-type {
            display: none;
        }
    
        /* Hide "Laravel" branding */
        [class*="filament-brand"] {
            display: none !important;
        }

        .fi-simple-header .fi-logo {
            height: 75px !important;
            width: auto !important;
        }
    </style>
</x-filament-panels::page.simple>