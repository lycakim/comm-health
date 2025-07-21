{{-- resources/views/filament/create-preview.blade.php --}}
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Form Data Display --}}
    <div>
        @php
            // Debug: Log the form data structure
            \Log::info('Form Data in Blade:', $formData ?? []);
            
            // Ensure $formData is an array
            $formData = $formData ?? [];
            
            // Filter out empty/null values and handle objects
            $groupedData = collect($formData)->map(function($value, $key) {
                // Handle Eloquent models (like User objects)
                if (is_object($value) && method_exists($value, 'toArray')) {
                    return $value->name ?? $value->title ?? "ID: {$value->id}" ?? 'Object';
                }
                if (is_object($value)) {
                    return 'Object';
                }
                return $value;
            })->reject(function($value) {
                return is_null($value) || 
                       (is_string($value) && trim($value) === '') || 
                       (is_array($value) && empty($value));
            });
        @endphp

        @if($groupedData->count() > 0)
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                <div class="space-y-0">
                    @php 
                        $chunks = $groupedData->chunk(2);
                    @endphp
                    
                    @forelse($chunks as $chunkIndex => $chunk)
                        @if($chunk->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200 dark:divide-gray-700 {{ !$loop->first ? 'border-t border-gray-200 dark:border-gray-700' : '' }}">
                                @foreach($chunk as $key => $value)
                                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                        <div class="flex flex-col space-y-2">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                {{ ucwords(str_replace(['_', '-'], ' ', $key)) }}
                                            </dt>
                                            <dd class="text-sm text-gray-900 dark:text-gray-100">
                                                @php
                                                    // Convert value to string for processing
                                                    $stringValue = is_string($value) ? $value : (string)$value;
                                                @endphp

                                                {{-- Handle Arrays --}}
                                                @if(is_array($value))
                                                    @if(count($value) > 0)
                                                        <div class="flex flex-wrap gap-2">
                                                            @foreach($value as $itemIndex => $item)
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                                    {{ is_array($item) ? json_encode($item) : $item }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-gray-400 italic">Empty array</span>
                                                    @endif
                                                
                                                {{-- Handle Boolean-like Values --}}
                                                @elseif(in_array($stringValue, ['0', '1']) && !str_contains($key, '_id') && $key !== 'type')
                                                    @if($stringValue === '1')
                                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-100 dark:bg-green-900/30">
                                                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                            <span class="text-green-700 dark:text-green-300 font-medium text-xs">Yes</span>
                                                        </div>
                                                    @else
                                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-100 dark:bg-red-900/30">
                                                            <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                            <span class="text-red-700 dark:text-red-300 font-medium text-xs">No</span>
                                                        </div>
                                                    @endif
                                                
                                                {{-- Handle User ID (special case) --}}
                                                @elseif($key === 'user_id')
                                                    @php
                                                        $displayName = $value;
                                                        // If it's still an object, try to get a display name
                                                        if (is_object($value)) {
                                                            $displayName = $value->name ?? $value->title ?? "User ID: {$value->id}" ?? 'User Object';
                                                        }
                                                    @endphp
                                                    <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                        </svg>
                                                        {{ $displayName }}
                                                    </span>

                                                {{-- Handle Type Field --}}
                                                @elseif($key === 'type')
                                                    @php
                                                        try {
                                                            $personType = \App\Models\PersonType::find($value);
                                                            $typeName = $personType?->name ?? "Type ID: {$value}";
                                                        } catch (\Exception $e) {
                                                            $typeName = "Type ID: {$value}";
                                                        }
                                                    @endphp
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                        </svg>
                                                        {{ $typeName }}
                                                    </span>
                                                
                                                {{-- Handle Other ID Fields --}}
                                                @elseif(str_contains($key, '_id') || str_ends_with($key, '_id'))
                                                    <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                                        </svg>
                                                        ID: {{ $value }}
                                                    </span>
                                                
                                                {{-- Handle Regular Numeric Values --}}
                                                @elseif(is_numeric($value))
                                                    <span class="font-mono text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                                        {{ number_format((float)$value, is_float((float)$value) ? 2 : 0) }}
                                                    </span>

                                                {{-- Handle Email --}}
                                                @elseif(filter_var($stringValue, FILTER_VALIDATE_EMAIL))
                                                    <div class="flex items-center gap-1.5">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                        </svg>
                                                        <a href="mailto:{{ $stringValue }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                            {{ $stringValue }}
                                                        </a>
                                                    </div>
                                                
                                                {{-- Handle Long Text --}}
                                                @elseif(strlen($stringValue) > 100)
                                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-3 max-h-auto max-w-auto break-all overflow-hidden">
                                                        <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-all font-sans">{{ $stringValue }}</pre>
                                                    </div>
                                                
                                                {{-- Handle Dates --}}
                                                @elseif(preg_match('/^\d{4}-\d{2}-\d{2}/', $stringValue))
                                                    @php
                                                        try {
                                                            if($key === 'birth_date') {
                                                                $formattedDate = \Carbon\Carbon::parse($stringValue)->format('F j, Y');
                                                            } else {
                                                                $formattedDate = \Carbon\Carbon::parse($stringValue)->format('F j, Y \a\t g:i A');
                                                            }
                                                        } catch (\Exception $e) {
                                                            $formattedDate = $stringValue;
                                                        }
                                                    @endphp
                                                    <div class="flex items-center gap-1.5">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                        <span class="font-medium">{{ $formattedDate }}</span>
                                                    </div>
                                                
                                                {{-- Handle Status --}}
                                                @elseif($key === 'status')
                                                    @php
                                                        $statusColors = [
                                                            'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                                            'inactive' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                                            'draft' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                            'published' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                            'archived' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                                            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                            'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                        ];
                                                        $statusColor = $statusColors[strtolower($stringValue)] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
                                                    @endphp
                                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                                                        {{ ucfirst($stringValue) }}
                                                    </span>
                                                
                                                {{-- Handle URLs --}}
                                                @elseif(filter_var($stringValue, FILTER_VALIDATE_URL))
                                                    <div class="flex items-center gap-1.5">
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                                                        </svg>
                                                        <a href="{{ $stringValue }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline truncate max-w-xs">
                                                            {{ preg_replace('#^https?://#', '', $stringValue) }}
                                                        </a>
                                                    </div>
                                                
                                                {{-- Handle Default Text --}}
                                                @else
                                                    <span class="break-words">{{ $stringValue }}</span>
                                                @endif
                                            </dd>
                                        </div>
                                    </div>
                                @endforeach
                                
                                {{-- Fill empty column if odd number of items --}}
                                @if($chunk->count() === 1)
                                    <div class="p-4 hidden md:block"></div>
                                @endif
                            </div>
                        @endif
                    @empty
                        <div class="p-8 text-center">
                            <p class="text-gray-500 dark:text-gray-400">No data chunks to display</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center">
                <div class="flex flex-col items-center justify-center gap-3">
                    <div class="rounded-full bg-gray-100 dark:bg-gray-700 p-3">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-gray-900 dark:text-white font-medium">No Data Available</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">There is no data to preview at this time.</p>
                </div>
            </div>
        @endif
    </div>
</div>