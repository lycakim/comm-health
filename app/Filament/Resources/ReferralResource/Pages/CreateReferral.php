<?php

namespace App\Filament\Resources\ReferralResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\MaxWidth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ReferralResource;

class CreateReferral extends CreateRecord
{
    protected static string $resource = ReferralResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
                        return 'Confirm details';
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
                        if (empty(array_filter($formData))) {
                            throw new \Exception('Please fill in all required fields before proceeding.');
                        }

                        return new HtmlString(
                            view('filament.create-preview', compact('formData'))->render()
                        );
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $html = '<div id="Outer" class="flex flex-col w-full h-full overflow-auto gap-3">';

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
                // Guard against null components or components without getName method
                if ($component === null || !method_exists($component, 'getName')) {
                    continue;
                }
                
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