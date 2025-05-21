<?php

namespace App\Filament\Resources\PatientResource\Pages;

use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\PatientResource;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        if (!auth()->user()->isMHO()) {
            return [
                Actions\CreateAction::make(),
            ];
        }
        return [];
    }

    // public function getTabs(): array
    // {
    //     return [
    //         'all' => Tab::make('All')->modifyQueryUsing(function ($query) {
    //             return $query->latest();
    //         }),
    //     ];
    // }

    public function getSubheading(): string|Htmlable|null
    {
        if (auth()->user()->barangays->count() > 0) {
            return 'View and manage patient records across barangay ' . auth()->user()->barangays->first()->name;
        }
        return 'View and manage patient records across all barangays';
    }
}