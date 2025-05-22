<div class="rounded-lg overflow-hidden">
    <div class="px-6 py-4">
        <h3 class="text-lg font-medium text-gray-900">Review Your Information</h3>
        <p class="text-sm text-gray-500">Please confirm these details before proceeding</p>
    </div>
    
    <div class="p-6 space-y-6">
        @foreach($data as $key => $value)
            <div class="flex pb-4">
                <div class="w-1/3">
                    <p class="text-sm font-medium text-gray-600">{{ Str::headline($key) }}</p>
                </div>
                <div class="w-2/3">
                    @if(is_array($value))
                        <div class="rounded-md p-3">
                            <p class="text-xs text-gray-500 mb-1">Array Data</p>
                            @foreach($value as $arrayKey => $arrayValue)
                                <div class="flex text-sm py-1">
                                    <span class="font-medium text-gray-700 mr-2">{{ is_numeric($arrayKey) ? "#$arrayKey" : $arrayKey }}:</span>
                                    <span class="text-gray-900">{{ is_array($arrayValue) ? json_encode($arrayValue) : (strlen($arrayValue) ? $arrayValue : '—') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @elseif(filter_var($value, FILTER_VALIDATE_EMAIL))
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="text-blue-600">{{ $value }}</span>
                        </div>
                    @elseif(Str::contains(strtolower($key), ['date', 'time', 'created', 'updated']))
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-gray-900">{{ $value }}</span>
                        </div>
                    @elseif(strlen($value) > 100)
                        <div class="bg-gray-50 rounded-md p-3">
                            <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $value }}</p>
                        </div>
                    @elseif(strlen($value))
                        <p class="text-sm text-gray-900">{{ $value }}</p>
                    @else
                        <span class="text-sm text-gray-400 italic">Not provided</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    
    <div class="px-6 py-4 flex justify-end space-x-3">
        <p class="text-xs text-gray-500 mr-auto pt-2">Please review carefully before confirming</p>
    </div>
</div>