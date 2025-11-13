<div class="space-y-6">
    <div class="p-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Patient Information Preview</h3>
        
        {{-- Personal Information --}}
        <div class="grid grid-cols-1 gap-4 mb-6">
            <div class="space-y-3 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-400">Personal Details</h4>
                
                @if(!empty($formData['first_name']) || !empty($formData['last_name']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Full Name:</span>
                        </div>
                        <span class="text-sm">
                            {{ collect([$formData['first_name'] ?? '', $formData['middle_name'] ?? '', $formData['last_name'] ?? ''])->filter()->implode(' ') }}
                        </span>
                    </div>
                @endif

                @if(!empty($formData['suffix']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-identification class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Suffix:</span>
                        </div>
                        <span class="text-sm">{{ $formData['suffix'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['birth_date']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-calendar class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Birth Date:</span>
                        </div>
                        <span class="text-sm">{{ $formData['birth_date'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['age']))
                    <div class="flex items-center justify-between">
                         <div class="flex items-center">
                             <x-heroicon-o-clock class="w-4 h-4 text-gray-500" />
                             <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Age: </span>
                        </div>
                        <span class="text-sm">{{ $formData['age'] }} year/s</span>
                    </div>
                @endif

                @if(!empty($formData['sex']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user-group class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Gender: </span>
                        </div>
                        <span class="text-sm">{{ ucfirst($formData['sex']) }}</span>
                    </div>
                @endif

                @if(!empty($formData['civil_status']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-heart class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Civil Status: </span>
                        </div>
                        <span class="text-sm">{{ ucfirst($formData['civil_status']) }}</span>
                    </div>
                @endif

                @if(!empty($formData['barangay_id']) && !empty($formData['purok_id']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-home-modern class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">House Address:</span>
                        </div>
                        <span class="text-sm">
                            {{ collect([$formData['purok_name'] ?? '', $formData['barangay_name'] ?? ''])->filter()->implode(' ') }}
                        </span>
                    </div>
                @endif
                
                @if(!empty($formData['relationship_to_head_of_family']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-sm text-gray-700 dark:text-gray-500">Relationship to Head of Family: </span>
                        </div>
                        <span class="text-sm">{{ ucfirst($formData['relationship_to_head_of_family']) }}</span>
                    </div>
                @endif

                @if(!empty($formData['category_id']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-sm text-gray-700 dark:text-gray-500">Category: </span>
                        </div>
                        <span class="text-sm">{{ ucfirst($formData['category_name']) }}</span>
                    </div>
                @endif

                @if(!empty($formData['place_of_birth']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-sm text-gray-700 dark:text-gray-500">Place of Birth: </span>
                        </div>
                        <span class="text-sm">{{ ucfirst($formData['place_of_birth']) }}</span>
                    </div>
                @endif
            </div>

            <div class="space-y-3 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-400">Additional Information</h4>
                
                @if(!empty($formData['educational_attainment']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-academic-cap class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Education:</span>
                        </div>
                        <span class="text-sm">{{ $formData['educational_attainment'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['occupation']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-academic-cap class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Occupation:</span>
                        </div>
                        <span class="text-sm">{{ $formData['occupation'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['family_monthly_income']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-banknotes class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Monthly Income:</span>
                        </div>
                        <span class="text-sm">{{ $formData['family_monthly_income'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['no_of_house']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-banknotes class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Number of House:</span>
                        </div>
                        <span class="text-sm">{{ $formData['no_of_house'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['house_type']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-home-modern class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">House Type:</span>
                        </div>
                        <span class="text-sm">{{ $formData['house_type'] }}</span>
                    </div>
                @endif
            </div>
            
            @if(!empty($formData['height']) || !empty($formData['weight']) || !empty($formData['bmi']) || !empty($formData['bmi_category']) || !empty($formData['trained_for_first_aid']) || (isset($formData['medication_maintenance']) && count($formData['medication_maintenance']) > 0) || (isset($formData['water_supply_sources']) && count($formData['water_supply_sources']) > 0) || !empty($formData['toilet_types']) || !empty($formData['drainage_disposals']) || !empty($formData['livestock']))
            <div class="space-y-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-400">Health Metrics</h4>
                
                @if(!empty($formData['height']) || !empty($formData['weight']) || !empty($formData['bmi']))
                    <div class="flex justify-between">
                        <div class="flex-col items-center text-center">
                            <div class="flex items-center">
                                <x-heroicon-o-arrow-up class="w-4 h-4 text-gray-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Height</span>
                            </div>
                            <span class="text-sm">{{ $formData['height'] }} cm</span>
                        </div>
                        <div class="flex-col items-center text-center">
                            <div class="flex items-center">
                                <x-heroicon-o-scale class="w-4 h-4 text-gray-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Weight</span>
                            </div>
                            <span class="text-sm">{{ $formData['weight'] }} kg</span>
                        </div>
                        <div class="flex-col items-center text-center">
                            <div class="flex items-center">
                                <x-heroicon-o-chart-bar class="w-4 h-4 text-gray-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">BMI</span>
                            </div>
                            <span class="text-sm">{{ $formData['bmi'] }} kg</span>
                        </div>
                    </div>
                @endif

                @if(!empty($formData['blood_pressure']) || !empty($formData['sugar_level']) || !empty($formData['bmi_category']))
                    <div class="flex justify-between">
                        <div class="flex-col items-center text-center">
                            <div class="flex items-center">
                                <x-heroicon-o-heart class="w-4 h-4 text-gray-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Blood Pressure</span>
                            </div>
                            <span class="text-sm">{{ $formData['blood_pressure'] }} mm Hg</span>
                        </div>
                        <div class="flex-col items-center text-center">
                            <div class="flex items-center">
                                <x-heroicon-o-beaker class="w-4 h-4 text-gray-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Sugar Level</span>
                            </div>
                            <span class="text-sm">{{ $formData['sugar_level'] }} mg/dl</span>
                        </div>
                        <div class="flex-col items-center text-center">
                            <div class="flex items-center">
                                <x-heroicon-o-user class="w-4 h-4 text-pink-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">BMI Category</span>
                            </div>
                            <span class="text-sm">{{ ucfirst($formData['bmi_category']) }}</span>
                        </div>
                    </div>
                @endif

                @if(isset($formData['medication_maintenance']) && count($formData['medication_maintenance']) > 0)
                    <div class="flex justify-center text-center">
                        <div class="flex-col justify-center items-center">
                            <div class="flex">
                                <x-heroicon-o-user class="w-4 h-4 text-gray-500" />
                                <span class="text-sm text-gray-700 dark:text-gray-500 text-center" style="margin-left: 0.2rem;">Medicine Maintenance</span>
                            </div>
                            <span class="text-sm">
                                {{ collect($formData['medication_maintenance'] ?? [])->filter()->implode(', ') }}
                            </span>
                        </div>
                    </div>
                @endif
            </div> 
            @endif
            
            @if(!empty($formData['water_supply_sources']) || !empty($formData['toilet_types']) || !empty($formData['drainage_disposals']) || (isset($formData['livestock']) && count($formData['livestock']) > 0))
            <div class="space-y-3 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-400">Other Information</h4>
                
                @if(!empty($formData['water_supply_sources']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Source of Water Supply:</span>
                        </div>
                        <span class="text-sm">{{ ucfirst($formData['water_supply_sources']) }}</span>
                    </div>
                @endif
                
                @if(!empty($formData['toilet_types']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Toilet Type:</span>
                        </div>
                        <span class="text-sm">{{ ucfirst($formData['toilet_types']) }}</span>
                    </div>
                @endif
                
                @if(!empty($formData['drainage_disposals']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Drainagen Disposal:</span>
                        </div>
                        <span class="text-sm">{{ ucfirst($formData['drainage_disposals']) }}</span>
                    </div>
                @endif

                @if(isset($formData['livestock']) || count($formData['livestock']) > 0)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">LiveStock:</span>
                        </div>
                        <span class="text-sm">{{ collect($formData['livestock'] ?? [])->filter()->implode(', ') }}</span>
                    </div>
                @endif
            </div>
            @endif

            {{-- consultation preview --}}
            <div class="space-y-3 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-400">Consultation Details</h4>
                
                @if(!empty($formData['disability']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Disability:</span>
                        </div>
                        <span class="text-sm">{{ $formData['disability'] ? 'Yes' : 'No' }}</span>
                    </div>
                @endif

                @if(isset($formData['disabilities']) && count($formData['disabilities']) > 0)
                    <div class="flex justify-between items-center">
                        <div class="flex">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-500 text-center" style="margin-left: 0.2rem;">Medicine Maintenance</span>
                        </div>
                        <span class="text-sm">
                            {{ collect($formData['disabilities'] ?? [])->filter()->implode(', ') }}
                        </span>
                    </div>
                @endif

                @if(!empty($formData['philhealth']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">PhilHealth:</span>
                        </div>
                        <span class="text-sm">{{ $formData['philhealth'] ? 'Yes' : 'No' }}</span>
                    </div>
                @endif

                @if(!empty($formData['member_of_4ps']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">4Ps member:</span>
                        </div>
                        <span class="text-sm">{{ $formData['member_of_4ps'] ? 'Yes' : 'No' }}</span>
                    </div>
                @endif

                @if(!empty($formData['nhts_member']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">NHTS Member:</span>
                        </div>
                        <span class="text-sm">{{ $formData['nhts_member'] ? 'Yes' : 'No' }}</span>
                    </div>
                @endif

                @if(!empty($formData['birth_plan']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Birth Plan:</span>
                        </div>
                        <span class="text-sm">{{ $formData['birth_plan'] ? 'Yes' : 'No' }}</span>
                    </div>
                @endif

                @if(!empty($formData['notes']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Consultation Notes:</span>
                        </div>
                        <span class="text-sm">{{ $formData['notes'] }}</span>
                    </div>
                @endif
            </div>

            {{-- referral preview --}}
            <div class="space-y-3 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h4 class="font-medium text-sm text-gray-700 dark:text-gray-400">Referral Details</h4>
                
                @if(!empty($formData['referral_id']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Referral ID:</span>
                        </div>
                        <span class="text-sm">{{ $formData['referral_id'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['referral_date']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-calendar class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Referral Date:</span>
                        </div>
                        <span class="text-sm">{{ $formData['referral_date'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['referred_to']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Referred To:</span>
                        </div>
                        <span class="text-sm">{{ $formData['referred_to'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['referral_reason']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Referral Reason:</span>
                        </div>
                        <span class="text-sm">{{ $formData['referral_reason'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['reason_for_referral_other']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Other Reason:</span>
                        </div>
                        <span class="text-sm">{{ $formData['reason_for_referral_other'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['urgency']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Urgency:</span>
                        </div>
                        <span class="text-sm">{{ $formData['urgency'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['surgical_operation']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Surgical Operation:</span>
                        </div>
                        <span class="text-sm">{{ $formData['surgical_operation'] ? 'Yes' : 'No' }}</span>
                    </div>
                @endif

                @if(!empty($formData['procedure']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Procedure:</span>
                        </div>
                        <span class="text-sm">{{ $formData['procedure'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['drug_allergy']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Drug Allergy:</span>
                        </div>
                        <span class="text-sm">{{ $formData['drug_allergy'] ? 'Yes' : 'No' }}</span>
                    </div>
                @endif

                @if(!empty($formData['drug_allergy_notes']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Drug Allergy Notes:</span>
                        </div>
                        <span class="text-sm">{{ $formData['drug_allergy_notes'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['chief_complaint']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Chief Complaint:</span>
                        </div>
                        <span class="text-sm">{{ $formData['chief_complaint'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['action_taken']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Action Taken:</span>
                        </div>
                        <span class="text-sm">{{ $formData['action_taken'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['impression']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Impression:</span>
                        </div>
                        <span class="text-sm">{{ $formData['impression'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['hpi_notes']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">HPI Notes:</span>
                        </div>
                        <span class="text-sm">{{ $formData['hpi_notes'] }}</span>
                    </div>
                @endif

                @if(!empty($formData['notes']))
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <x-heroicon-o-user class="w-4 h-4 text-gray-500 mr-4" />
                            <span class="text-sm text-gray-700 dark:text-gray-500" style="margin-left: 0.2rem;">Additional Notes:</span>
                        </div>
                        <span class="text-sm">{{ $formData['notes'] }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>