<?php

namespace App\Filament\Resources\ConsultationResource\Pages;

use Filament\Actions;
use App\Models\Consultation;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\ActionSize;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Infolists\Components\TextEntry;
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

    // protected function getFormActions(): array
    // {
    //     return [
    //         Action::make('create')
    //             ->label('Create')
    //             ->size(ActionSize::Large)
    //             ->icon('heroicon-o-plus')
    //             ->color('primary')
    //             ->modalWidth('md')
    //             ->modalHeading('Create New Record')
    //             ->modalSubmitActionLabel('Confirm & Submit')
    //             ->modalCancelActionLabel('Back')
    //             ->requiresConfirmation() // Optional: makes modal act like a preview/confirm step
    //             ->beforeFormFilled(function () {
    //                 if (empty($this->form->getState()['patient_id'])) {
    //                     Notification::make()
    //                         ->title('The patient field is required before creating.')
    //                         ->danger()
    //                         ->send();

    //                     return false; // Prevents modal from opening
    //                 }
    //             })
    //             ->modalContent(fn () => $this->renderPreviewModal())
    //             ->action(fn () => $this->handleCreateAction()),
    //     ];
    // }
}