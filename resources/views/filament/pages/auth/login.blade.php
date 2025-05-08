<x-filament-panels::page.simple>
    <div class="flex flex-col items-center justify-center space-y-2 text-center">
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

    <div class="text-center mt-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Doesn't have an account? 
            <a href="{{ route('register') }}" class="text-green-600 hover:text-green-700 font-medium">
                Sign Up
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
    </style>
</x-filament-panels::page.simple>