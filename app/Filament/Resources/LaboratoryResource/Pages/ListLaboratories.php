<?php

namespace App\Filament\Resources\LaboratoryResource\Pages;

use App\Filament\Resources\LaboratoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class ListLaboratories extends ListRecords
{
    use HasDashboardBreadcrumb;

    protected static string $resource = LaboratoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
