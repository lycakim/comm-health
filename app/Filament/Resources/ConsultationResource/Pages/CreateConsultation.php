<?php

namespace App\Filament\Resources\ConsultationResource\Pages;

use Filament\Actions;
use Livewire\Component;
use App\Models\Consultation;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\MaxWidth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ConsultationResource;

class CreateConsultation extends CreateRecord
{
    protected static string $resource = ConsultationResource::class;

    // protected function handleRecordCreation(array $data): Consultation
    // {
    //     if ($data['status'] === 'completed' && empty($data['follow_up_date'])) {
    //         $data['follow_up_date'] = now()->addWeeks(2);
    //     }

    //     return Consultation::create($data);
    // }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Consultation Created!')
            ->success()
            ->body('Consultation has been created and saved.');
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
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
                ->modalWidth(MaxWidth::FiveExtraLarge)
                ->modalDescription(function () {
                    try {
                        $this->form->validate();

                        $formData = $this->form->getState();
                        logger($formData);
                        if (empty(array_filter($formData))) {
                            throw new \Exception('Please fill in all required fields before proceeding.');
                        }

                        return new HtmlString(
                            view('filament.create-preview', compact('formData'))->render()
                        );
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $html = '<div  class="flex flex-col w-full h-full overflow-auto gap-3">';

                        foreach ($e->errors() as $fieldErrors) {
                            $html .= "<div class=\"flex items-center gap-2 p-3 rounded bg-red-100";
                            foreach ($fieldErrors as $message) {
                                $html .=  "
                                            <x-heroicon-o-exclamation-circle class=\"w-5 h-5\" />
                                            <span>{$message}</span>
                                        ";
                            }
                            $html .= '</div>';
                        }

                        $html .= '</div>';

                        return new HtmlString($html);
                    } catch (\Exception $e) {
                        // Fallback for general exceptions
                        $html = '<div class="flex items-center gap-2 p-3 rounded bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">
                                <x-heroicon-o-exclamation-circle class="w-5 h-5" />
                                <span>{$e->getMessage()}</span>
                            </div>';

                        return new HtmlString($html);
                    }
                })
                ->action(function () {
                    $this->create();
                })
                ->keyBindings(['mod+s']);
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

    protected function getPersonTypeName($value)
    {
        try {
            // Get the form schema to find the relationship
            $schema = $this->form->getSchema();
            
            foreach ($schema as $component) {
                if ($component->getName() === 'type') {
                    $relationship = $component->getRelationship();
                    $relatedModel = $component->getRelationship()->getRelated();
                    $record = $relatedModel::find($value);
                    
                    if ($record) {
                        return $record->name ??
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