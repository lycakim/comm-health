<?php

namespace App\Filament\Resources\FamilyRelationshipResource\Pages;

use App\Filament\Concerns\HasDashboardBreadcrumb;
use App\Filament\Resources\FamilyRelationshipResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateFamilyRelationship extends CreateRecord
{
    use HasDashboardBreadcrumb;

    protected static string $resource = FamilyRelationshipResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Family Relationship Created!')
            ->success()
            ->body('The relationship has been added successfully.');
    }
}
