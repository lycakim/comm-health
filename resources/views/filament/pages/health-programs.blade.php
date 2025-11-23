<x-filament-panels::page>
    @push('styles')
    <style>
        /* REMOVE all z-index overrides */
        
        /* Remove transform ONLY for the calendar container */
        .calendar-container {
            transform: none !important;
        }
    </style>
    @endpush


    <div class="flex">
        <div class="flex flex-col w-64">
            <x-filament::tabs class="flex flex-col justify-start w-full space-y-4">
                <div class="flex justify-start gap-2">
                    <x-filament::tabs.item 
                        alpine-active="$wire.activeTab === 'calendar'" 
                        :active="$activeTab === 'calendar'" 
                        wire:click="$set('activeTab', 'calendar')">
                        Calendar
                    </x-filament::tabs.item>
                    
                    <x-filament::tabs.item 
                        alpine-active="$wire.activeTab === 'upcoming'" 
                        :active="$activeTab === 'upcoming'" 
                        wire:click="$set('activeTab', 'upcoming')">
                        Upcoming
                    </x-filament::tabs.item>

                    <x-filament::tabs.item 
                        alpine-active="$wire.activeTab === 'past'" 
                        :active="$activeTab === 'past'" 
                        wire:click="$set('activeTab', 'past')">
                        Past Programs
                    </x-filament::tabs.item>
                </div>
            </x-filament::tabs>
        </div>
    </div>

    {{-- Calendar Tab Content --}}
    <div x-show="$wire.activeTab === 'calendar'">
        <div class="grid w-full grid-cols-1 gap-4">
            <div class="calendar-container block p-4 rounded-lg border transition-colors duration-200
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600">
                
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-200">
                  Program Calendar
                </h3>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 transition-colors duration-200">
                    View upcoming health programs.
                </p>
                
                <div class="mt-6">
                    @livewire(App\Filament\Widgets\CalendarWidget::class)
                </div>
            </div>
        </div>
    </div>

    {{-- Upcoming Tab Content --}}
    <div x-show="$wire.activeTab === 'upcoming'">
        <div class="grid w-full grid-cols-1 gap-4">
            <div class="calendar-container block p-4 rounded-lg border transition-colors duration-200
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600">
                
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-200">
                    Upcoming Programs
                </h3>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 transition-colors duration-200">
                    Health programs scheduled for the next 30 days.
                </p>
                
                <div class="mt-6">
                    @livewire(App\Filament\Widgets\UpcomingProgramWidget::class)
                </div>
            </div>
        </div>
    </div>

    {{-- Past Programs Tab Content --}}
    <div x-show="$wire.activeTab === 'past'">
        <div class="grid w-full grid-cols-1 gap-4">
            <div class="calendar-container block p-4 rounded-lg border transition-colors duration-200
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600">
                
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-200">
                    Past Programs
                </h3>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 transition-colors duration-200">
                    Health programs scheduled for the passed 30 days.
                </p>
                
                <div class="mt-6">
                    @livewire(App\Filament\Widgets\PastProgramWidget::class)
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>