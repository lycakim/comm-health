<?php

namespace App\Filament\Resources\ReferralResource\Pages;

use Filament\Actions;
use App\Models\Barangay;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ReferralResource;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class ListReferrals extends ListRecords
{
    use HasDashboardBreadcrumb;

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

    public function getTabs(): array
    {
        return [];
    }
}