<?php

namespace App\Filament\Resources\ReferralResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\ReferralResource;

class ViewReferral extends ViewRecord
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        if (Auth::user()->isBHW() || Auth::user()->isMidwife()) {
            return [
                Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('gray'),
            ];
        }
        return [
            Actions\EditAction::make()
                ->color('gray'),
        ];
    }
}