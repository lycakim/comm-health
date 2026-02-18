<x-filament-panels::page>
    <div class="space-y-8">
        {{-- Upload form --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="p-6">
                {{ $this->uploadForm }}
                <div class="mt-3">
                    <x-filament::button wire:click="uploadPhotos" color="primary" type="button">
                        Upload photos
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- Current photos --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 mt-3">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Photos on landing page</h3>
                    @php
                        $photos = $this->getLandingPhotos();
                        $photoCount = count($photos);
                    @endphp
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $photoCount }} photo{{ $photoCount === 1 ? '' : 's' }}
                    </span>
                </div>
                @if ($photoCount === 0)
                    <p class="text-sm text-gray-500 dark:text-gray-400">No photos yet. Upload photos above to show them in the landing page carousel.</p>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        @foreach ($photos as $photo)
                            <div class="relative group rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                <img
                                    src="{{ $photo['url'] }}"
                                    alt="Landing photo {{ $photo['index'] }}"
                                    class="w-full aspect-[3/4] object-cover"
                                />
                                <div class="absolute inset-x-0 bottom-0 p-2 bg-gradient-to-t from-black/70 to-transparent flex items-center justify-between">
                                    <span class="text-xs text-white truncate">{{ $photo['filename'] }}</span>
                                    <x-filament::button
                                        size="xs"
                                        color="danger"
                                        icon="heroicon-o-trash"
                                        wire:click="removePhoto('{{ e($photo['filename']) }}')"
                                        wire:confirm="Are you sure you want to remove this photo?"
                                        class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium bg-red-200 text-white hover:bg-danger-500"
                                    >
                                        Remove
                                    </x-filament::button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
