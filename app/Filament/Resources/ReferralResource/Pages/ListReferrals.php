<?php

namespace App\Filament\Resources\ReferralResource\Pages;

use Filament\Actions;
use App\Models\Barangay;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Components\Tab;
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

    public function getTabs(): array
    {
        if (Auth::user()->isMHO() || Auth::user()->isAdmin()) {
            return [
                'all' => Tab::make('All')
                        ->modifyQueryUsing(function ($query) {
                            return $query->latest();
                        })
                        ->badge(fn () => $this->getModel()::count()),
                'pending' => Tab::make('Pending')
                        ->modifyQueryUsing(function ($query) {
                            return $query->where('status', 'pending')->latest();
                        })
                        ->badge(fn () => $this->getModel()::where('status', 'pending')->count())
                        ->badgeColor('warning'),
                'accepted' => Tab::make('Accepted')
                        ->modifyQueryUsing(function ($query) {
                            return $query->where('status', 'accepted')->latest();
                        })
                        ->badge(fn () => $this->getModel()::where('status', 'accepted')->count()),
                'completed' => Tab::make('Completed')
                        ->modifyQueryUsing(function ($query) {
                            return $query->where('status', 'completed')->latest();
                        })
                        ->badge(fn () => $this->getModel()::where('status', 'completed')->count()),
                'cancelled' => Tab::make('Cancelled')
                        ->modifyQueryUsing(function ($query) {
                            return $query->where('status', 'cancelled')->latest();
                        })
                        ->badge(fn () => $this->getModel()::where('status', 'cancelled')->count())
                        ->badgeColor('danger'),
            ];
        }
        return [];
    }
}