<div
    x-cloak
    x-show="$store.loadingOverlay?.visible ?? false"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[999999] flex items-center justify-center bg-gray-900/50 dark:bg-gray-950/60 backdrop-blur-sm"
>
    <div class="flex flex-col items-center gap-4 rounded-xl bg-white dark:bg-gray-900 px-8 py-6 shadow-xl">
        <x-filament::loading-indicator class="h-10 w-10 text-primary-500" />
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Loading...</p>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('loadingOverlay', {
            visible: false,
            delayTimer: null,
            set(value) {
                clearTimeout(this.delayTimer);
                if (value === false) {
                    this.visible = false;
                } else {
                    this.delayTimer = setTimeout(() => {
                        this.visible = true;
                    }, 300);
                }
            }
        });
    });

    document.addEventListener('livewire:init', () => {
        document.addEventListener('livewire:navigate', () => {
            const store = Alpine.store('loadingOverlay');
            if (store) store.set(true);
        });
        document.addEventListener('livewire:navigated', () => {
            const store = Alpine.store('loadingOverlay');
            if (store) store.set(false);
        });
    });
</script>
