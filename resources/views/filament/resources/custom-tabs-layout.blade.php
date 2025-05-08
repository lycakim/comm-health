<div class="flex flex-col space-y-2 mr-6">
    @foreach($tabs as $key => $tab)
        <a 
            class="px-3 py-2 text-sm rounded-lg {{ $tab['isActive'] ? 'bg-primary-500 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700' }}"
        >
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>