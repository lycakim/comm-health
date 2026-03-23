<?php

namespace App\Filament\Resources\FamilyRelationshipResource\Pages;

use App\Filament\Concerns\HasDashboardBreadcrumb;
use App\Filament\Resources\FamilyRelationshipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFamilyRelationships extends ListRecords
{
    use HasDashboardBreadcrumb;

    protected static string $resource = FamilyRelationshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }
}
