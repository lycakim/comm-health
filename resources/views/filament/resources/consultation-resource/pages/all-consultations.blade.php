<x-filament-panels::page>    
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;" class="w-full">
        @foreach($barangays as $barangay)
            <a href="{{ \App\Filament\Resources\ConsultationResource::getUrl('list', ['barangay' => $barangay->id]) }}"
               class="barangay-card group block p-4 rounded-lg border transition-all duration-200 ease-in-out
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300 hover:shadow-md
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600
                      transform hover:scale-105">

                <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">{{ $barangay->name }}</h3>

                <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                    {{ $barangay->consultations_count }} {{ Str::plural('consultation', $barangay->consultations_count) }}
                </p>
            </a>
        @endforeach
    </div>
    <style>
        /* Additional custom animations if needed */
        .barangay-card {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .barangay-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        /* Dark mode shadow adjustments */
        .dark .barangay-card {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
        }
        
        .dark .barangay-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4), 0 2px 4px -1px rgba(0, 0, 0, 0.3);
        }
    </style>
</x-filament-panels::page>