<?php

namespace App\Filament\Resources\ConsultationResource\Pages;

use Filament\Actions;
use App\Models\Consultation;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ConsultationResource;

class CreateConsultation extends CreateRecord
{
    protected static string $resource = ConsultationResource::class;

    protected function handleRecordCreation(array $data): Consultation
    {
        if ($data['status'] === 'completed' && empty($data['follow_up_date'])) {
            $data['follow_up_date'] = now()->addWeeks(2);
        }

        return Consultation::create($data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Consultation Created!')
            ->success()
            ->body('Consultation has been created and saved.');
    }
}