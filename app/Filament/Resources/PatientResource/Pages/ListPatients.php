<?php

namespace App\Filament\Resources\PatientResource\Pages;

use Filament\Actions;
use App\Models\Barangay;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\PatientResource;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        if (!auth()->user()->isMHO()) {
            return [
                Actions\CreateAction::make()
                    ->icon('heroicon-o-plus'),
            ];
        }
        return [];
    }

    public function getTabs(): array
    {
        if (auth()->user()->isMHO()) {
            $tabs = [
                'all' => Tab::make('All')
                    ->modifyQueryUsing(function ($query) {
                        return $query->latest();
                    })
                    ->badge(fn () => $this->getModel()::count()),
            ];
    
            if (auth()->user()->isMHO()) {
                $barangays = Barangay::all();
            } else {
                $barangays = auth()->user()->barangays;
            }
    
            // Create a tab for each barangay
            foreach ($barangays as $barangay) {
                $tabs[$barangay->id] = Tab::make($barangay->name)
                    ->modifyQueryUsing(function ($query) use ($barangay) {
                        return $query->where('barangay_id', $barangay->id)->latest();
                    })
                    ->badge(fn () => $this->getModel()::where('barangay_id', $barangay->id)->count());
            }
    
            return $tabs;
        }
        return [];
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (auth()->user()->barangays->count() > 0) {
            return 'View and manage patient records across barangay ' . auth()->user()->barangays->first()->name;
        }
        return 'View and manage patient records across all barangays';
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Get barangay from route parameter
        $barangayFromRoute = request()->route('barangay');
        
        if ($barangayFromRoute) {
            // If barangay is in route, filter by that barangay
            return $query->where('barangay_id', $barangayFromRoute);
        } else {
            // If no barangay in route, filter by authenticated user's barangay
            // This happens when BHW accesses the index route
            return $query->where('barangay_id', Auth::user()->barangay_id);
        }
    }
}