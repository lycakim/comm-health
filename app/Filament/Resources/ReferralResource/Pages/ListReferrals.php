<?php

namespace App\Filament\Resources\ReferralResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ReferralResource;

class ListReferrals extends ListRecords
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        if (Auth::user()->isMHO()) {
            return [];
        }

        if (Auth::user()->isAdmin() ) {
            return [
                Actions\CreateAction::make()
                    ->icon('heroicon-o-plus'),
            ];
        }
        return [];
    }
}