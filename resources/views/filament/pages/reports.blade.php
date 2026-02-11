<x-filament-panels::page x-data="{ tab: 'tab1' }">
    <div class="flex">
        <div class="flex flex-col w-64" >
            <x-filament::tabs class="flex flex-col justify-start w-full space-y-4">
                <div class="flex justify-start gap-2">
                    <x-filament::tabs.item 
                        active @click="tab = 'tab1'" :alpine-active="'tab === \'tab1\''">
                        Program Reports
                    </x-filament::tabs.item>
                    
                    <x-filament::tabs.item 
                        @click="tab = 'tab2'" :alpine-active="'tab === \'tab2\''">
                        Report Template
                    </x-filament::tabs.item>
                    
                    <x-filament::tabs.item 
                        @click="tab = 'tab3'" :alpine-active="'tab === \'tab3\''">
                        Generated Reports
                    </x-filament::tabs.item>
                </div>
            </x-filament::tabs>
        </div>
    </div>
    
    <div x-show="tab === 'tab1'">
        {{ $this->table }}
    </div>
    <div x-show="tab === 'tab2'" x-cloak>
        <div class="grid w-full grid-cols-1 md:grid-cols-2 gap-4">
            <div class="template-card group block p-4 rounded-lg border transition-all duration-200 ease-in-out
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300 hover:shadow-md
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600
                      transform hover:scale-105">
                
                <div class="flex justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">
                        Resident Profiling Report
                    </h3>
                    <x-filament::badge icon="heroicon-m-calendar">
                        Monthly
                    </x-filament::badge>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                    Patient Profiling
                </p>

                <div class="flex justify-between mt-6">
                    <x-filament::button 
                        color="gray" 
                        icon="heroicon-o-eye"
                        wire:click="previewReport('patient-profiling')"
                        wire:loading.attr="disabled"
                        wire:target="previewReport">
                        <span wire:loading.remove wire:target="previewReport">Preview</span>
                        <span wire:loading wire:target="previewReport" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Loading...
                        </span>
                    </x-filament::button>
                    <x-filament::button 
                        icon="heroicon-o-plus"
                        wire:click="openGenerateReportModal('patient-profiling', 'monthly')"
                        wire:loading.attr="disabled"
                        wire:target="openGenerateReportModal">
                        <span wire:loading.remove wire:target="openGenerateReportModal">Generate Report</span>
                        <span wire:loading wire:target="openGenerateReportModal" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Loading...
                        </span>
                    </x-filament::button>
                </div>
            </div>
            <div class="template-card group block p-4 rounded-lg border transition-all duration-200 ease-in-out
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300 hover:shadow-md
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600
                      transform hover:scale-105">
                
                <div class="flex justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">
                        Maternal and Child Report
                    </h3>
                    <x-filament::badge icon="heroicon-m-calendar">
                        Quarterly
                    </x-filament::badge>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                    Maternal Care
                </p>

                <div class="flex justify-between mt-6">
                    <x-filament::button 
                        color="gray" 
                        icon="heroicon-o-eye"
                        wire:click="previewReport('maternal-child')"
                        wire:loading.attr="disabled"
                        wire:target="previewReport">
                        <span wire:loading.remove wire:target="previewReport">Preview</span>
                        <span wire:loading wire:target="previewReport" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Loading...
                        </span>
                    </x-filament::button>
                    <x-filament::button 
                        icon="heroicon-o-plus"
                        wire:click="openGenerateReportModal('maternal-child', 'quarterly')"
                        wire:loading.attr="disabled"
                        wire:target="openGenerateReportModal">
                        <span wire:loading.remove wire:target="openGenerateReportModal">Generate Report</span>
                        <span wire:loading wire:target="openGenerateReportModal" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Loading...
                        </span>
                    </x-filament::button>
                </div>
            </div>
            <div class="template-card group block p-4 rounded-lg border transition-all duration-200 ease-in-out
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300 hover:shadow-md
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600
                      transform hover:scale-105">
                
                <div class="flex justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">
                        Senior Citizens Health Status Report
                    </h3>
                    <x-filament::badge icon="heroicon-m-calendar">
                        Quarterly
                    </x-filament::badge>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                    Geriatric Care
                </p>

                <div class="flex justify-between mt-6">
                    <x-filament::button 
                        color="gray" 
                        icon="heroicon-o-eye"
                        wire:click="previewReport('senior-citizens')"
                        wire:loading.attr="disabled"
                        wire:target="previewReport">
                        <span wire:loading.remove wire:target="previewReport">Preview</span>
                        <span wire:loading wire:target="previewReport" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Loading...
                        </span>
                    </x-filament::button>
                    <x-filament::button 
                        icon="heroicon-o-plus"
                        wire:click="openGenerateReportModal('senior-citizens', 'quarterly')"
                        wire:loading.attr="disabled"
                        wire:target="openGenerateReportModal">
                        <span wire:loading.remove wire:target="openGenerateReportModal">Generate Report</span>
                        <span wire:loading wire:target="openGenerateReportModal" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Loading...
                        </span>
                    </x-filament::button>
                </div>
            </div>
            <div class="template-card group block p-4 rounded-lg border transition-all duration-200 ease-in-out
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300 hover:shadow-md
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600
                      transform hover:scale-105">
                
                <div class="flex justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">
                        Family Planning Usage Report
                    </h3>
                    <x-filament::badge icon="heroicon-m-calendar">
                        Quarterly
                    </x-filament::badge>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                    Reproductive Health
                </p>

                <div class="flex justify-between mt-6">
                    <x-filament::button 
                        color="gray" 
                        icon="heroicon-o-eye"
                        wire:click="previewReport('family-planning')"
                        wire:loading.attr="disabled"
                        wire:target="previewReport">
                        <span wire:loading.remove wire:target="previewReport">Preview</span>
                        <span wire:loading wire:target="previewReport" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Loading...
                        </span>
                    </x-filament::button>
                    <x-filament::button 
                        icon="heroicon-o-plus"
                        wire:click="openGenerateReportModal('family-planning', 'quarterly')"
                        wire:loading.attr="disabled"
                        wire:target="openGenerateReportModal">
                        <span wire:loading.remove wire:target="openGenerateReportModal">Generate Report</span>
                        <span wire:loading wire:target="openGenerateReportModal" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Loading...
                        </span>
                    </x-filament::button>
                </div>
            </div>
            <div class="template-card group block p-4 rounded-lg border transition-all duration-200 ease-in-out
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300 hover:shadow-md
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600
                      transform hover:scale-105">
                
                <div class="flex justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">
                        Morbidity and Mortality Report
                    </h3>
                    <x-filament::badge icon="heroicon-m-calendar">
                        Quarterly
                    </x-filament::badge>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                    Disease Surveillance
                </p>

                <div class="flex justify-between mt-6">
                    <x-filament::button 
                        color="gray" 
                        icon="heroicon-o-eye"
                        wire:click="previewReport('morbidity-mortality')"
                        wire:loading.attr="disabled"
                        wire:target="previewReport">
                        <span wire:loading.remove wire:target="previewReport">Preview</span>
                        <span wire:loading wire:target="previewReport" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Loading...
                        </span>
                    </x-filament::button>
                    <x-filament::button 
                        icon="heroicon-o-plus"
                        wire:click="openGenerateReportModal('morbidity-mortality', 'quarterly')"
                        wire:loading.attr="disabled"
                        wire:target="openGenerateReportModal">
                        <span wire:loading.remove wire:target="openGenerateReportModal">Generate Report</span>
                        <span wire:loading wire:target="openGenerateReportModal" class="flex items-center gap-2">
                            <x-filament::loading-indicator class="h-4 w-4" />
                            Loading...
                        </span>
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
    <div x-show="tab === 'tab3'" x-cloak>
        {{ $this->generatedReportsTable }}
    </div>
    <style>
        /* Additional custom animations if needed */
        .template-card {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .template-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        /* Dark mode shadow adjustments */
        .dark .template-card {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
        }
        
        .dark .template-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4), 0 2px 4px -1px rgba(0, 0, 0, 0.3);
        }
    </style>
</x-filament-panels::page>