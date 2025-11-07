<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use App\Notifications\UserCreatedNotification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['barangay_id']);
        return $data;
    }

    protected function afterCreate(): void
    {
        // notify user that he/she was added to the system
        $this->record->notify(new UserCreatedNotification($this->record));

        logger($this->record);

        $barangayId = $this->form->getState()['barangay_id'];
        if ($barangayId) {
            $this->record->barangays()->attach($barangayId);
        }
    }
}