<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use App\Models\Patient;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class IndexPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    public function mount(): void
    {
        parent::mount();
        
        if (Auth::user()->isMHO() || Auth::user()->isAdmin()) {
            redirect()->to(PatientResource::getUrl('all'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }

    // protected function getTableQuery(): ?Builder
    // {
    //     // For newer Filament versions, use this method instead of getTableQuery()
    //     return static::getResource()::getEloquentQuery()->where('barangay_id', Auth::user()->barangay_id);
    // }
}