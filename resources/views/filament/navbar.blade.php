<div class="flex items-center gap-4">
    <div class="text-right">
        <div class="text-sm font-semibold text-gray-700 dark:text-gray-400">
            {{ auth()->user()->name }}
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
            {{ auth()->user()->role->getLabel() }}
        </div>
    </div>
</div>