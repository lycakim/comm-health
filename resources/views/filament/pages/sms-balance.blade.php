<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Balance Details
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::button 
                wire:click="checkBalance"
                color="gray"
            >
                <x-slot name="icon">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </x-slot>
                Refresh
            </x-filament::button>
        </x-slot>

        {{-- content --}}
        @if(!empty($balanceData))
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Account Name</p>
                    <p class="text-xl font-semibold">{{ $balance['account_name'] ?? 'N/A' }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Balance</p>
                    <p class="text-xl font-semibold text-green-600">
                        ₱{{ number_format($balance['credit_balance'] ?? 0, 2) }}
                    </p>
                </div>
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-gray-500">Unable to fetch balance. Please check your API key.</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
