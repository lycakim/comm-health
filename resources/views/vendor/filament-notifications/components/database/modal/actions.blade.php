@props([
    'notifications',
    'unreadNotificationsCount',
])

<div {{ $attributes->class('mt-2 flex gap-x-3') }}>
    @if ($unreadNotificationsCount)
        <x-filament::link
            color="primary"
            tabindex="-1"
            tag="button"
            wire:click="markAllNotificationsAsRead"
        >
            {{ __('filament-notifications::database.modal.actions.mark_all_as_read.label') }}
        </x-filament::link>
    @endif

    {{-- Clear button removed - notifications should not be deleted --}}
</div>
