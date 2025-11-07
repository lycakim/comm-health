<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Models\Patient;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Bus;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\Sodium\Core\Curve25519\H;
use Filament\Notifications\Notification;
use App\Jobs\NotificationEmailPatientJob;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PatientResource;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    protected function handleRecordCreation(array $data): Patient
    {
        $data['user_id'] = Auth::id();

        return Patient::create($data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Patient Added!')
            ->success()
            ->body('You have successfully added a patient.');
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
                ->label('Submit')
                ->before(function () {  
                    try {
                        $this->form->validate();
                        
                        $formData = $this->form->getState();
                        if (empty(array_filter($formData))) {
                            throw new \Exception('Please fill in all required fields before proceeding.');
                        }
                        
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Form Incomplete')
                            ->body($e->getMessage())
                            ->warning()
                            ->send();
                        
                        $this->halt();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading(function (){
                    try {
                        $this->form->validate();

                        $formData = $this->form->getState();
                        if (empty(array_filter($formData))) {
                            throw new \Exception('Please fill in all required fields before proceeding.');
                        }
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        return 'Fill in all required fields before proceeding.';
                    } catch (\Exception $e) {
                        return 'Fill in all required fields before proceeding.';
                    }
                })
                ->modalWidth(MaxWidth::TwoExtraLarge)
                ->modalDescription(function () {
                    try {
                        $this->form->validate();

                        $formData = $this->form->getState();
                        if (empty(array_filter($formData))) {
                            throw new \Exception('Please fill in all required fields before proceeding.');
                        }

                        if (!empty($formData['category_id'])) {
                            $formData['category_name'] = \App\Models\Category::find($formData['category_id'])->name;
                        }

                        if (!empty($formData['barangay_id']) && !empty($formData['purok_id'])) {
                            $formData['barangay_name'] = \App\Models\Barangay::find($formData['barangay_id'])->name;
                            $formData['purok_name'] = \App\Models\Purok::find($formData['purok_id'])->name;
                        }

                        // Remove dd() for production - uncomment for debugging
                        // dd($formData);

                        return new HtmlString(
                            view('filament.create-preview', compact('formData'))->render()
                        );
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $html = '<div id="Outer" class="flex flex-col w-full h-full overflow-auto gap-3">';

                        foreach ($e->errors() as $fieldErrors) {
                            $html .= '<div class="flex items-center gap-2 p-3 rounded bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">';
                            
                            foreach ($fieldErrors as $message) {
                                $html .= '
                                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <span>' . htmlspecialchars($message) . '</span>
                                ';
                            }
                            
                            $html .= '</div>';
                        }

                        $html .= '</div>';

                        return new HtmlString($html);
                    } catch (\Exception $e) {
                        // Fallback for general exceptions
                        $html = '<div class="flex items-center gap-2 p-3 rounded bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span>' . htmlspecialchars($e->getMessage()) . '</span>
                            </div>';

                        return new HtmlString($html);
                    }
                })
                ->action(function () {
                    // check if the full name is already exists in the database 
                    // if first name and last name are the same then check the middle name and suffix
                    
                    // Check if the patient already exists
                    $existingPatient = Patient::where('first_name', $this->form->getState()['first_name'])
                        ->where('last_name', $this->form->getState()['last_name'])
                        ->where('middle_name', $this->form->getState()['middle_name'])
                        ->where('suffix', $this->form->getState()['suffix'])
                        ->first();

                    if ($existingPatient) {
                        Notification::make()
                            ->title('Patient already exists')
                            ->body('The patient with the same name already exists in the database.')
                            ->warning()
                            ->send();

                        $this->halt();
                    }
                    $this->create();
                    // Get the newly created patient record
                    $newPatient = $this->getRecord();
                    
                    // Now dispatch the job with the actual patient
                    // Bus::dispatch(new NotificationEmailPatientJob($newPatient));
                })
                ->keyBindings(['mod+s']);
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return Action::make('createAnother')
            ->label('Submit & Create Another')
            ->action('createAnother')
            ->keyBindings(['mod+shift+s'])
            ->color('gray');
    }

    protected function getRelationshipDisplayName($key, $value)
    {
        try {
            // Get the form schema to find the relationship
            $schema = $this->form->getSchema();
            
            foreach ($schema as $component) {
                if ($component->getName() === $key && method_exists($component, 'getRelationship')) {
                    $relationship = $component->getRelationship();
                    $relatedModel = $component->getRelationship()->getRelated();
                    $record = $relatedModel::find($value);
                    
                    if ($record) {
                        // Try common display fields
                        return $record->name ?? 
                            $record->title ?? 
                            $record->label ?? 
                            $record->display_name ?? 
                            "ID: {$value}";
                    }
                }
            }
            
            return "ID: {$value}";
        } catch (\Exception $e) {
            return "ID: {$value}";
        }
    }
}