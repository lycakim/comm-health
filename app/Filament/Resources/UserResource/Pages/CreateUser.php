<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use App\Notifications\UserCreatedNotification;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['barangay_id']);
        return $data;
    }

    protected function afterCreate(): void
    {
        try {
            // Check if user has an email
            if (empty($this->record->email)) {
                logger()->error('Cannot send notification: User has no email', [
                    'user_id' => $this->record->id,
                ]);
                return;
            }
    
            // Log the attempt
            logger()->info('Attempting to send user created notification', [
                'user_id' => $this->record->id,
                'user_email' => $this->record->email,
            ]);

            // Send notification
            $this->record->notify(new UserCreatedNotification($this->record));

            // Log success
            logger()->info('User created notification queued successfully', [
                'user_id' => $this->record->id,
            ]);

            // Show success message in Filament
            Notification::make()
                ->title('User created and notification sent!')
                ->success()
                ->send();

        } catch (\Exception $e) {
            // Log error
            logger()->error('Failed to send user created notification', [
                'user_id' => $this->record->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Show error in Filament
            Notification::make()
                ->title('User created but email failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        // Set barangay_id directly
        $barangayId = $this->form->getState()['barangay_id'] ?? null;
        if ($barangayId) {
            $this->record->barangay_id = $barangayId;
            $this->record->save();
        }
    }
}