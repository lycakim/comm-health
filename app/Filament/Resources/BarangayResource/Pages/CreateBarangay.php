<?php

namespace App\Filament\Resources\BarangayResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\BarangayResource;

class CreateBarangay extends CreateRecord
{
    protected static string $resource = BarangayResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Barangay Added!')
            ->success()
            ->body('You have successfully added a barangay.');
    }
}