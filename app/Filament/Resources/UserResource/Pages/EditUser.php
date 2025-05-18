<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (isset($data['barangay_id'])) {
            $record->barangays()->sync($data['barangay_id']);
        }

        return $record;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('User Updated!')
            ->success()
            ->body('User has been updated.');
    }
}