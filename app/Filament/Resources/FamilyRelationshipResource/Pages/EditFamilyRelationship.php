<?php

namespace App\Filament\Resources\FamilyRelationshipResource\Pages;

use App\Filament\Concerns\HasDashboardBreadcrumb;
use App\Filament\Resources\FamilyRelationshipResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditFamilyRelationship extends EditRecord
{
    use HasDashboardBreadcrumb;

    protected static string $resource = FamilyRelationshipResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Family Relationship Updated!')
            ->success()
            ->body('The relationship has been updated successfully.');
    }
}
