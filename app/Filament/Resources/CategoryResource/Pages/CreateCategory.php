<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\CategoryResource;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class CreateCategory extends CreateRecord
{
    use HasDashboardBreadcrumb;

    protected static string $resource = CategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Category Created!')
            ->success()
            ->body('You have successfully created a categroy.');
    }
}