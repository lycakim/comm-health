{{-- resources/views/filament/create-preview.blade.php --}}
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Form Data Display --}}
    <div>
        @php
            $groupedData = collect($formData)->reject(function($value) {
                return is_null($value) || (is_string($value) && trim($value) === '') || (is_array($value) && empty($value));
            });
        @endphp

        @if($groupedData->count() > 0)
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200 dark:divide-gray-700">
                    @php $count = 0; @endphp
                    @foreach($groupedData as $key => $value)
                        @php 
                            $count++; 
                            $isNewRow = $count % 2 !== 0;
                        @endphp
                        
                        @if($isNewRow && $count > 1)
                            </div><div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-200 dark:divide-gray-700 border-t border-gray-200 dark:border-gray-700">
                        @endif
                        
                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                            <div class="flex flex-col space-y-2">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ ucwords(str_replace(['_', '-'], ' ', $key)) }}
                                </dt>
                                <dd class="text-sm text-gray-900 dark:text-gray-100">
                                    {{-- Handle Arrays (including relationships) --}}
                                    @if(is_array($value))
                                        @if(count($value) > 0)
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($value as $item)
                                                    {{-- Check if it's a relationship ID --}}
                                                    @if(is_numeric($item) && (str_contains($key, '_id') || str_contains($key, 'ids')))
                                                        @php
                                                            $relationshipName = $this->getRelationshipDisplayName($key, $item);
                                                        @endphp
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                            <x-heroicon-o-link class="w-3 h-3 mr-1" />
                                                            {{ $relationshipName ?? "ID: {$item}" }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                            {{ is_array($item) ? json_encode($item) : $item }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    
                                    {{-- Handle Boolean Values --}}
                                    @elseif(is_bool($value) || in_array($value, ['0', '1', 0, 1]) || in_array(strtolower($value), ['true', 'false', 'yes', 'no']))
                                        @php
                                            $boolValue = is_bool($value) ? $value : (
                                                in_array($value, ['1', 1, 'true', 'yes']) ? true : false
                                            );
                                        @endphp
                                        @if($boolValue)
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-100 dark:bg-green-900/30">
                                                <x-heroicon-o-check-circle class="w-4 h-4 text-green-600 dark:text-green-400" />
                                                <span class="text-green-700 dark:text-green-300 font-medium text-xs">Yes</span>
                                            </div>
                                        @else
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-100 dark:bg-red-900/30">
                                                <x-heroicon-o-x-circle class="w-4 h-4 text-red-600 dark:text-red-400" />
                                                <span class="text-red-700 dark:text-red-300 font-medium text-xs">No</span>
                                            </div>
                                        @endif
                                    
                                    {{-- Handle Single Relationship IDs --}}
                                    @elseif(is_numeric($value) && (str_contains($key, '_id') || str_ends_with($key, '_id')))
                                        @php
                                            $relationshipName = $this->getRelationshipDisplayName($key, $value);
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                            <x-heroicon-o-link class="w-3 h-3 mr-1" />
                                            {{ $relationshipName ?? "ID: {$value}" }}
                                        </span>
                                    
                                    {{-- Handle Regular Numeric Values --}}
                                    @elseif(is_numeric($value))
                                        <span class="font-mono text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                            {{ number_format($value, is_float($value) ? 2 : 0) }}
                                        </span>

                                    {{-- Handle Email --}}
                                    @elseif(filter_var($value, FILTER_VALIDATE_EMAIL))
                                        <div class="flex items-center gap-1.5">
                                            <x-heroicon-o-envelope class="w-4 h-4 text-gray-400" />
                                            <a href="mailto:{{ $value }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                {{ $value }}
                                            </a>
                                        </div>
                                    
                                    {{-- Handle Long Text --}}
                                    @elseif(strlen($value) > 100)
                                        <div class=" dark:bg-gray-700 rounded-md p-3 max-h-auto max-w-auto break-all overflow-hidden">
                                            <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-all font-sans">{{ $value }}</pre>
                                        </div>
                                    
                                    {{-- Handle Dates (excluding birth_date) --}}
                                    @elseif(preg_match('/^\d{4}-\d{2}-\d{2}/', $value) && $key !== 'birth_date')
                                        <div class="flex items-center justify-center gap-1.5">
                                            <x-heroicon-o-calendar-days class="w-4 h-4 text-gray-400" />
                                            <span class="font-medium">
                                                {{ \Carbon\Carbon::parse($value)->format('F j, Y \a\t g:i A') }}
                                            </span>
                                        </div>
                                    
                                    {{-- Handle Birth Date --}}
                                    @elseif(preg_match('/^\d{4}-\d{2}-\d{2}/', $value) && $key === 'birth_date')
                                        <div class="flex items-center justify-center gap-1.5">
                                            <x-heroicon-o-calendar-days class="w-4 h-4 text-gray-400" />
                                            <span class="font-medium">
                                                {{ \Carbon\Carbon::parse($value)->format('F j, Y') }}
                                            </span>
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
                                            $statusColor = $statusColors[strtolower($value)] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
                                        @endphp
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                                            {{ ucfirst($value) }}
                                        </span>
                                    
                                    {{-- Handle URLs --}}
                                    @elseif(filter_var($value, FILTER_VALIDATE_URL))
                                        <div class="flex items-center gap-1.5">
                                            <x-heroicon-o-globe-alt class="w-4 h-4 text-gray-400" />
                                            <a href="{{ $value }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline truncate max-w-xs">
                                                {{ preg_replace('#^https?://#', '', $value) }}
                                            </a>
                                        </div>
                                    
                                    {{-- Handle Default Text --}}
                                    @else
                                        <span class="break-words">{{ $value }}</span>
                                    @endif
                                </dd>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-8 text-center">
                <div class="flex flex-col items-center justify-center gap-3">
                    <div class="rounded-full bg-gray-100 dark:bg-gray-700 p-3">
                        <x-heroicon-o-document-text class="w-8 h-8 text-gray-400" />
                    </div>
                    <h3 class="text-gray-900 dark:text-white font-medium">No Data Available</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">There is no data to preview at this time.</p>
                </div>
            </div>
        @endif
    </div>
</div>