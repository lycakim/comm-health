<x-filament-panels::page.simple>
    <div class="flex flex-col items-center justify-center space-y-2 text-center" style="margin-top: -35px; color: #087757;">
         <h2 class="text-2xl font-bold">
            CommHealth
        </h2>
    </div>
    
    <div class="flex flex-col items-center justify-center space-y-2 text-center" style="margin-top: -25px;">
        <h2 class="text-2xl font-bold">
            Verify Your Email Address
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
            Please verify your email address to continue.
        </p>
    </div>

    <x-filament-panels::form wire:submit="resend">
        <x-filament::button type="submit" class="w-full bg-green-600 hover:bg-green-700">
            Resend Verification Email
        </x-filament::button>
    </x-filament-panels::form>

    <div class="text-center mt-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            <a href="{{ route('filament.commhealth.auth.login') }}" class="text-green-600 hover:text-green-700 font-medium">
                ← Back to Login
            </a>
        </p>
    </div>

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
