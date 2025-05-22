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
        if (!auth()->user()->isMHO()) {
            return [
                Actions\CreateAction::make()
                    ->icon('heroicon-o-plus'),
            ];
        }
        return [];
    }

    // public function getTabs(): array
    // {
    //     if (auth()->user()->isMHO()) {
    //         $tabs = [
    //             'all' => Tab::make('All')
    //                 ->modifyQueryUsing(function ($query) {
    //                     return $query->latest();
    //                 })
    //                 ->badge(fn () => $this->getModel()::count()),
    //         ];
    
    //         if (auth()->user()->isMHO()) {
    //             $barangays = Barangay::all();
    //         } else {
    //             $barangays = auth()->user()->barangays;
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
        // Try to get barangay from multiple sources to handle refresh scenarios
        $barangayFromRoute = request()->route('barangay') ?? 
                            request()->get('barangay') ?? 
                            session('current_barangay') ??
                            $this->getRecord()?->barangay_id;

        if ($barangayFromRoute) {
            $barangay = Barangay::where('id', $barangayFromRoute)->first();

            if (!$barangay) {
                return 'View and manage patient records across all barangays';
            }
            
            // Store in session to persist across refreshes
            session(['current_barangay' => $barangay->id]);
            
            return 'View and manage patient records across barangay ' . $barangay->name;
        }
        
        return 'View and manage patient records across all barangays';
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        $barangayFromRoute = request()->route('barangay') ?? 
                            request()->get('barangay') ?? 
                            session('current_barangay') ??
                            $this->getRecord()?->barangay_id;
        
        if ($barangayFromRoute) {
            return $query->where('barangay_id', $barangayFromRoute);
        } else {
            return $query->where('barangay_id', Auth::user()->barangay_id);
        }
    }
}