<?php

namespace App\Filament\Resources\ConsultationResource\Pages;

use App\Filament\Resources\ConsultationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsultations extends ListRecords
{
    protected static string $resource = ConsultationResource::class;

    protected function getHeaderActions(): array
    {
        if (auth()->user()->isMHO()) {
            return [];
        }
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->hidden(fn () => auth()->user()->isResident()),
        ];
    }
}