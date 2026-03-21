<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Filament\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class ListAnnouncements extends ListRecords
{
    use HasDashboardBreadcrumb;

    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
        ];
    }
}