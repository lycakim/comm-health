<?php

namespace App\Filament\Resources\ReferralResource\Pages;

use App\Filament\Resources\ReferralResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReferral extends ViewRecord
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        if (auth()->user()->isBHW() || auth()->user()->isMidwife()) {
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