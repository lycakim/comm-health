<x-filament-panels::page.simple>
    <div class="flex flex-col items-center justify-center space-y-2 text-center" style="margin-top: -25px;">
        <h2 class="text-2xl font-bold">
            Create Account
        </h2>
        <span class="text-sm text-gray-600 dark:text-gray-400">
            Register to access CommHealth service
        </span>
    </div>

    <x-filament-panels::form wire:submit="register">
        {{ $this->form }}

        <x-filament::button type="submit" form="authenticate" class="w-full bg-green-600 hover:bg-green-700">
            Register
        </x-filament::button>
    </x-filament-panels::form>

    <div class="text-center mt-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Already have an account? 
            <a href="{{ route('login') }}" class="text-green-800 hover:text-green-700 font-medium">
                Login
            </a>
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
            <a href="/" class="text-green-600 hover:text-green-700 font-medium">
                Back to Home
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

        .fi-simple-main {
            @apply relative sm:max-w-sm #{!important};
            max-width: 600px !important;
        }

        .fi-simple-header .fi-logo {
            height: 35px !important;
            width: auto !important;
        }
    </style>
</x-filament-panels::page.simple>