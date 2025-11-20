<div class="space-y-6">
    {{-- Full Message --}}
    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
        <div class="flex items-start justify-between mb-2">
            <div class="flex items-center gap-2">
                <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-primary-500" />
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Full Message</h3>
            </div>
            <button 
                onclick="navigator.clipboard.writeText('{{ addslashes($record->message) }}'); 
                         $tooltip('Message copied!', { timeout: 2000 })"
                class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center gap-1"
            >
                <x-heroicon-o-clipboard class="w-4 h-4" />
                Copy
            </button>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ $record->message }}</p>
    </div>

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Recipient --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-2 mb-2">
                <x-heroicon-o-phone class="w-5 h-5 text-primary-500" />
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Recipient</h3>
            </div>
            <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $record->number }}</p>
        </div>

        {{-- Status --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-2 mb-2">
                <x-heroicon-o-signal class="w-5 h-5 text-primary-500" />
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Status</h3>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                @if(strtolower($record->status ?? '') === 'sent') bg-success-100 text-success-800 dark:bg-success-800 dark:text-success-100
                @elseif(strtolower($record->status ?? '') === 'queued') bg-info-100 text-info-800 dark:bg-info-800 dark:text-info-100
                @elseif(strtolower($record->status ?? '') === 'pending') bg-warning-100 text-warning-800 dark:bg-warning-800 dark:text-warning-100
                @elseif(strtolower($record->status ?? '') === 'failed') bg-danger-100 text-danger-800 dark:bg-danger-800 dark:text-danger-100
                @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100
                @endif
            ">
                {{ ucfirst($record->status ?? 'Unknown') }}
            </span>
        </div>

        {{-- Sender Name --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-2 mb-2">
                <x-heroicon-o-user class="w-5 h-5 text-primary-500" />
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Sender Name</h3>
            </div>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $record->sender_name ?? 'N/A' }}</p>
        </div>

        {{-- Message ID --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-2 mb-2">
                <x-heroicon-o-identification class="w-5 h-5 text-warning-500" />
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Message ID</h3>
            </div>
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $record->message_id ?? 'N/A' }}</p>
                @if($record->message_id)
                    <button 
                        onclick="navigator.clipboard.writeText('{{ $record->message_id }}'); 
                                 $tooltip('Message ID copied!', { timeout: 2000 })"
                        class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400"
                    >
                        <x-heroicon-o-clipboard class="w-4 h-4" />
                    </button>
                @endif
            </div>
        </div>

        {{-- Sent At --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-2 mb-2">
                <x-heroicon-o-clock class="w-5 h-5 text-success-500" />
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Sent At</h3>
            </div>
            <p class="text-sm text-gray-900 dark:text-gray-100">
                {{ $record->sent_at ? $record->sent_at->format('M d, Y h:i A') : 'N/A' }}
            </p>
        </div>

        {{-- Retrieved At --}}
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-2 mb-2">
                <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-success-500" />
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Retrieved At</h3>
            </div>
            <p class="text-sm text-gray-900 dark:text-gray-100">
                {{ $record->retrieved_at ? $record->retrieved_at->format('M d, Y h:i A') : 'N/A' }}
            </p>
        </div>
    </div>
</div>