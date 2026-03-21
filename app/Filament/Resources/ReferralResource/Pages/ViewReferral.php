<?php

namespace App\Filament\Resources\ReferralResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\ReferralResource;
use App\Filament\Concerns\HasDashboardBreadcrumb;
use App\Filament\Concerns\HasBackAction;

class ViewReferral extends ViewRecord
{
    use HasDashboardBreadcrumb;
    use HasBackAction;

    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        if (Auth::user()->isBHW() || Auth::user()->isMidwife()) {
            return [
                $this->getBackAction(),
                Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('gray'),
            ];
        }
        return [
            $this->getBackAction(),
            Actions\EditAction::make()
                ->color('gray'),
        ];
    }
}