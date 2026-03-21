<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\HasDashboardBreadcrumb;
use App\Filament\Concerns\HasBackAction;

class EditAnnouncement extends EditRecord
{
    use HasDashboardBreadcrumb;
    use HasBackAction;

    protected static string $resource = AnnouncementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\DeleteAction::make(),
        ];
    }
}