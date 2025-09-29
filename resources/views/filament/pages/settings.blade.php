<x-filament-panels::page x-data="{ tab: 'tab1' }">
    <div class="flex">
        <div class="flex flex-col w-64" >
            <x-filament::tabs class="flex flex-col justify-start w-full space-y-4">
                <div class="flex justify-start gap-2">
                    <x-filament::tabs.item 
                        active @click="tab = 'tab1'" :alpine-active="'tab === \'tab1\''">
                        Profile
                    </x-filament::tabs.item>
                    
                    <x-filament::tabs.item 
                        @click="tab = 'tab2'" :alpine-active="'tab === \'tab2\''">
                        Account Security
                    </x-filament::tabs.item>

                    <x-filament::tabs.item 
                        @click="tab = 'tab3'" :alpine-active="'tab === \'tab3\''">
                        Notification
                    </x-filament::tabs.item>

                    <x-filament::tabs.item 
                        @click="tab = 'tab4'" :alpine-active="'tab === \'tab4\''">
                        System Settings
                    </x-filament::tabs.item>

                    <x-filament::tabs.item 
                        @click="tab = 'tab5'" :alpine-active="'tab === \'tab5\''">
                        Privacy & System Use Policy
                    </x-filament::tabs.item>
                </div>
            </x-filament::tabs>
        </div>
    </div>
    
    <div x-show="tab === 'tab1'">
        <div class="grid w-full grid-cols-1 md:grid-cols-1 gap-4">
            <div class="template-card group block p-4 rounded-lg border transition-all duration-200 ease-in-out
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300 hover:shadow-md
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600
                      transform hover:scale-105">
                
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">
                    System Settings
                </h3>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                    Manage your personal information and profile settings.
                </p>
            </div>
        </div>
    </div>
    <div x-show="tab === 'tab2'" x-cloak>
        <div class="grid w-full grid-cols-1 md:grid-cols-1 gap-4">
            <div class="mt-5">
                {{ $this->form }}

                <div class="mt-6">
                    <x-filament::button wire:click.prevent="save">
                        Save Changes
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>
    <div x-show="tab === 'tab3'" x-cloak>
        <div class="grid w-full grid-cols-1 md:grid-cols-1 gap-4">
            <div class="template-card group block p-4 rounded-lg border transition-all duration-200 ease-in-out
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300 hover:shadow-md
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600
                      transform hover:scale-105">
                
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">
                    System Settings
                </h3>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                    Manage your personal information and profile settings.
                </p>
            </div>
        </div>
    </div>
    <div x-show="tab === 'tab4'" x-cloak>
        <div class="grid w-full grid-cols-1 md:grid-cols-1 gap-4">
            <div class="template-card group block p-4 rounded-lg border transition-all duration-200 ease-in-out
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300 hover:shadow-md
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600
                      transform hover:scale-105">

                <h3 class="text-2xl font-bold text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">
                    System Settings
                </h3>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                    Manage your personal information and profile settings.
                </p>
            </div>
        </div>
    </div>
    <div x-show="tab === 'tab5'" x-cloak class="space-y-3">
        <div class="grid w-full grid-cols-1 md:grid-cols-1 gap-4">
            <div class="template-card group block p-4 rounded-lg border transition-all duration-200 ease-in-out
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300 hover:shadow-md
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600
                      transform hover:scale-105">

                <h3 class="text-2xl font-bold text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">
                    Privacy & System Use Policy
                </h3>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                    Information about your privacy and system use.
                </p>
            </div>
        </div>
        <div class="grid w-full grid-cols-1 md:grid-cols-1 gap-4">
            <div class="template-card group block p-4 rounded-lg border transition-all duration-200 ease-in-out
                      bg-white hover:bg-green-50 border-gray-200 hover:border-green-300 hover:shadow-md
                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:border-gray-700 dark:hover:border-gray-600
                      transform hover:scale-105">
                <ul class="space-y-6">
                    <li>
                        <h3 class="text-xl text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">Confidentiality & Data Privacy</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                            All personal and health-related information collected, stored, and processed in this system is strictly confidential and is protected under the Data Privacy Act of 2012 (Republic Act No. 10173). Only authorized health personnel are allowed to access patient information.
                        </p>
                    </li>
                    <li>
                        <h3 class="text-xl text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">Authorized Use Only</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                            This system is intended for official use by the Municipal Health Office (MHO), Barangay Health Workers (BHWs), and Midwives. Unauthorized access, use, or sharing of data is strictly prohibited and may result in legal consequences.
                        </p>
                    </li>
                    <li>
                        <h3 class="text-xl text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">Accuracy of Information</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                            Users are responsible for ensuring that all data entered into the system is complete, accurate, and updated. Any misuse or intentional falsification of information may be subject to disciplinary or legal action.
                        </p>
                    </li>
                    <li>
                        <h3 class="text-xl text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">System Availability</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                            While we aim to keep the system available at all times, the MHO is not liable for any downtime, data loss, or technical issues that may occur due to factors beyond our control.
                        </p>
                    </li>
                    <li>
                        <h3 class="text-xl text-gray-900 dark:text-white group-hover:text-green-700 dark:group-hover:text-green-400 transition-colors duration-200">SMS Notifications</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 mt-1 transition-colors duration-200">
                            Patients may receive SMS alerts related to health programs and consultations. These messages are for informational purposes only and do not replace professional medical advice.
                        </p>
                    </li>
                </ul>
                
            </div>
        </div>
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