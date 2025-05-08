<div class="w-full">
    <x-filament::tabs class="w-full flex justify-center">
        <div class="flex w-full max-w-md justify-between gap-2">
            <x-filament::tabs.item 
                :active="$activeTab === 'tab1'" 
                wire:click="setActiveTab('tab1')"
                class="flex-1 text-center">
                MHO
            </x-filament::tabs.item>
            
            <x-filament::tabs.item 
                :active="$activeTab === 'tab2'" 
                wire:click="setActiveTab('tab2')"
                class="flex-1 text-center">
                Health Worker
            </x-filament::tabs.item>
            
            <x-filament::tabs.item 
                :active="$activeTab === 'tab3'" 
                wire:click="setActiveTab('tab3')"
                class="flex-1 text-center">
                Resident
            </x-filament::tabs.item>
        </div>
    </x-filament::tabs>
</div>