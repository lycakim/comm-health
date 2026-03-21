<?php

namespace App\Filament\Resources\BarangayResource\Pages;

use App\Filament\Resources\BarangayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class ListBarangays extends ListRecords
{
    use HasDashboardBreadcrumb;

    protected static string $resource = BarangayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }
}