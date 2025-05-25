<x-filament-panels::page x-data="{ tab: 'tab1' }">
    <div class="flex">
        <div class="flex flex-col w-64" >
            <x-filament::tabs class="flex flex-col justify-start w-full space-y-4">
                <div class="flex justify-start gap-2">
                    <x-filament::tabs.item 
                        active @click="tab = 'tab1'" :alpine-active="'tab === \'tab1\''">
                        Overview
                    </x-filament::tabs.item>
                    
                    <x-filament::tabs.item 
                        @click="tab = 'tab2'" :alpine-active="'tab === \'tab2\''">
                        Maternal
                    </x-filament::tabs.item>

                    <x-filament::tabs.item 
                        @click="tab = 'tab3'" :alpine-active="'tab === \'tab3\''">
                        Disease
                    </x-filament::tabs.item>
                </div>
            </x-filament::tabs>
        </div>
    </div>
    
    {{-- <div x-show="tab === 'tab1'">
        
    </div>
    <div x-show="tab === 'tab2'" x-cloak>

    </div> --}}
</x-filament-panels::page>