<?php

namespace App\Filament\Resources\PatientResource\Pages;

use Filament\Actions;
use App\Models\Barangay;
use Illuminate\Support\Facades\Auth;
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
        if (!Auth::user()->isMHO()) {
            return [
                Actions\CreateAction::make()
                    ->icon('heroicon-o-plus'),
            ];
        }
        return [];
    }

    // public function getTabs(): array
    // {
    //     if (Auth::user()->isMHO()) {
    //         $tabs = [
    //             'all' => Tab::make('All')
    //                 ->modifyQueryUsing(function ($query) {
    //                     return $query->latest();
    //                 })
    //                 ->badge(fn () => $this->getModel()::count()),
    //         ];
    
    //         if (Auth::user()->isMHO()) {
    //             $barangays = Barangay::all();
    //         } else {
    //             $barangays = Auth::user()->barangays;
    //         }
    
    //         foreach ($barangays as $barangay) {
    //             $tabs[$barangay->id] = Tab::make($barangay->name)
    //                 ->modifyQueryUsing(function ($query) use ($barangay) {
    //                     return $query->where('barangay_id', $barangay->id)->latest();
    //                 })
    //                 ->badge(fn () => $this->getModel()::where('barangay_id', $barangay->id)->count());
    //         }
    
    //         return $tabs;
    //     }
    //     return [];
    // }

    public function getSubheading(): string|Htmlable|null
    {
        $barangayFromRoute = request()->route('barangay');

        if ($barangayFromRoute) {
            $barangay = Barangay::where('id', $barangayFromRoute)->first();

            if (!$barangay) {
                return 'View and manage patient records across all barangays';
            }
            
            return 'View and manage patient records across barangay ' . $barangay->name;
        }
        
        return 'View and manage patient records across all barangays';
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        $barangayFromRoute = request()->route('barangay');
        
        if ($barangayFromRoute === 'all' || is_null($barangayFromRoute)) {
            return $query;
        }

        return $query->where('barangay_id', $barangayFromRoute);
    }
}