<x-filament-panels::page>
    <div class="space-y-4">
        @if($balance)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Account Information</h3>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Account Name</p>
                        <p class="text-xl font-semibold">{{ $balance['account_name'] ?? 'N/A' }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Balance</p>
                        <p class="text-xl font-semibold text-green-600">
                            ₱{{ number_format($balance['balance'] ?? 0, 2) }}
                        </p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <x-filament::button wire:click="checkBalance">
                        Refresh Balance
                    </x-filament::button>
                </div>
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-gray-500">Unable to fetch balance. Please check your API key.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
