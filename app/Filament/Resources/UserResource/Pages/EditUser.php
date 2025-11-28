<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            // "Create Another" button
            Actions\Action::make('create_another')
            ->label('Create Another')
            ->icon('heroicon-o-plus')
            ->color('success')
            ->url(fn () => UserResource::getUrl('create'))
            ->openUrlInNewTab(false), // set to true if you prefer opening in a new tab
            
            // Delete button (default Filament action)
            Actions\DeleteAction::make(),
        ];
    }


    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Update all fields including barangay_id directly
        $record->update($data);

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