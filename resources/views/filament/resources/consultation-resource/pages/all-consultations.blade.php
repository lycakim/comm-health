<x-filament-panels::page>    
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;" class="w-full">
        @foreach($barangays as $barangay)
            <a href="{{ \App\Filament\Resources\ConsultationResource::getUrl('list', ['barangay' => $barangay->id]) }}"
               class="border shadow-sm p-4 hover:bg-gray-50 transition-colors cursor-pointer block" style="border-radius: 5px;">
                <h3 class="text-lg font-bold">{{ $barangay->name }}</h3>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $barangay->consultations_count }} {{ Str::plural('consultation', $barangay->consultations_count) }}
                </p>
                <p class="text-xs text-gray-500">
                    {{ $barangay->patients_count }} {{ Str::plural('patient', $barangay->patients_count) }}
                </p>
            </a>
        @endforeach
    </div>
</x-filament-panels::page>