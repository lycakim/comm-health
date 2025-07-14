<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\ProgramResource;

class ListPrograms extends ListRecords
{
    protected static string $resource = ProgramResource::class;

    protected function getHeaderActions(): array
    {
        if (Auth::user()->isBHW() || Auth::user()->isMidwife()) {
            return [];
        }
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }

    // public function getTabs(): array
    // {
    //     return [
    //         'all' => Tab::make('All')->modifyQueryUsing(function ($query) {
    //             return $query->latest();
    //         }),
    //     ];
    // }

    // public function getSubheading(): string|Htmlable|null
    // {
    //     return 'Programs';
    // }
}