<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use App\Models\Patient;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    protected function handleRecordCreation(array $data): Patient
    {
        $data['user_id'] = auth()->id();

        return Patient::create($data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Patient Added!')
            ->success()
            ->body('You have successfully added a patient.');
    }
}