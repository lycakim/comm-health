<x-filament-panels::page>
{{ $this->form }}

    <div>
        <x-filament::button wire:click.prevent="save">
            Save Changes
        </x-filament::button>
    </div>
</x-filament-panels::page>
