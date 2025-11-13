<div>
    @if($showModal)
    <div 
        class="fixed inset-0 overflow-y-auto"
        style="z-index: 9999;"
        x-data="{ show: true }"
        x-show="show"
        x-cloak
    >
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-gray-950/75" style="backdrop-filter: blur(4px);"></div>
        
        {{-- Modal --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div 
                class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl"
                @click.away.prevent
                @keydown.escape.prevent
            >
                {{-- Header --}}
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Data Privacy Act Compliance
                        </h2>
                    </div>
                </div>

                {{-- Content --}}
                <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                    <div class="prose dark:prose-invert max-w-none">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                            Privacy Notice
                        </h3>
                        
                        <p class="text-sm text-gray-700 dark:text-gray-200 mb-4">
                            In compliance with the Data Privacy Act, we would like to inform you about how we collect, use, and protect your personal information.
                        </p>

                        <div class="space-y-3 text-sm text-gray-700 dark:text-gray-400">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">
                                    Information We Collect:
                                </h4>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Personal identification information (name, email address)</li>
                                    <li>Usage data and system logs</li>
                                    <li>Account credentials (securely encrypted)</li>
                                </ul>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">
                                    How We Use Your Information:
                                </h4>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>To provide and maintain our services</li>
                                    <li>To improve user experience</li>
                                    <li>To comply with legal obligations</li>
                                    <li>To communicate important updates</li>
                                </ul>
                            </div>

                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1">
                                    Your Rights:
                                </h4>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Right to access your personal data</li>
                                    <li>Right to correct inaccurate information</li>
                                    <li>Right to request deletion of your data</li>
                                    <li>Right to object to data processing</li>
                                </ul>
                            </div>
                        </div>

                        <p class="text-sm text-gray-700 dark:text-gray-400 mt-4">
                            By clicking "Accept and Continue", you acknowledge that you have read and understood this privacy notice and consent to the processing of your personal data as described.
                        </p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 dark:bg-gray-900/50 rounded-b-xl">
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span class="text-red-600">*</span> You must accept to continue using the system
                        </p>
                        <button 
                            wire:click="acceptPrivacy"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 disabled:bg-gray-400 text-white font-semibold rounded-lg transition-colors duration-200 shadow-sm"
                        >
                            <span wire:loading.remove>Accept and Continue</span>
                            <span wire:loading>
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>