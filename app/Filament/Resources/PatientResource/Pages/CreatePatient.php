<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Patient Added!')
            ->success()
            ->body('You have successfully added a patient.');
    }
}