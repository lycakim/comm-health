@php
    $matchingPatients = $matchingPatients ?? collect();
    $currentFirstName = $currentFirstName ?? '';
    $currentMiddleName = $currentMiddleName ?? '';
    $currentLastName = $currentLastName ?? '';
@endphp

<div 
    x-data="{ 
        showResults: true,
        selectPatient(patient) {
            // Use Filament's form state management to set field values
            // Filament forms use 'data' as the state path prefix
            @this.set('data.first_name', patient.first_name || '');
            @this.set('data.middle_name', patient.middle_name || '');
            @this.set('data.last_name', patient.last_name || '');
            @this.set('data.suffix', patient.suffix || '');
            
            // Optionally populate birth_date if available
            if (patient.birth_date) {
                @this.set('data.birth_date', patient.birth_date);
            }
            
            // Hide results after selection
            this.showResults = false;
        }
    }"
    class="mt-4"
>
    @if($matchingPatients->isNotEmpty())
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    <span class="text-primary-600 dark:text-primary-400">{{ $matchingPatients->count() }}</span> 
                    matching patient(s) found
                </p>
            </div>
            
            <div class="max-h-64 overflow-y-auto">
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($matchingPatients as $patient)
                        @php
                            $fullName = trim(
                                ($patient->first_name ?? '') . ' ' . 
                                ($patient->middle_name ?? '') . ' ' . 
                                ($patient->last_name ?? '') . ' ' . 
                                ($patient->suffix ?? '')
                            );
                            $age = $patient->birth_date ? \Carbon\Carbon::parse($patient->birth_date)->age : null;
                            $barangayName = $patient->barangay->name ?? 'N/A';
                        @endphp
                        
                        <li 
                            @click="selectPatient(@js($patient))"
                            class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-150"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                        {{ $fullName }}
                                    </p>
                                    <div class="mt-1 flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                        <div class="flex" style="gap: 10px;">
                                            @if($age !== null)
                                                <span>Age: {{ $age }} years</span>
                                            @endif
                                            @if($patient->birth_date)
                                                <span>DOB: {{ \Carbon\Carbon::parse($patient->birth_date)->format('M d, Y') }}</span>
                                            @endif
                                            <span>Barangay: {{ $barangayName }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @elseif(
        (strlen(trim($currentFirstName)) >= 2) ||
        (strlen(trim($currentMiddleName)) >= 2) ||
        (strlen(trim($currentLastName)) >= 2)
    )
        <div class="mt-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                No matching patients found. You can proceed to create a new patient.
            </p>
        </div>
    @endif
</div>