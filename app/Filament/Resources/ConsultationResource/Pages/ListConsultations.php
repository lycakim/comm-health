<?php

namespace App\Filament\Resources\ConsultationResource\Pages;

use Filament\Actions;
use App\Models\Barangay;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\ConsultationResource;

class ListConsultations extends ListRecords
{
    protected static string $resource = ConsultationResource::class;

    protected function getHeaderActions(): array
    {
        if (auth()->user()->isMHO()) {
            return [];
        }
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->hidden(fn () => auth()->user()->isResident()),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        $barangayFromRoute = request()->route('barangay');

        if ($barangayFromRoute) {
            $barangay = Barangay::where('id', $barangayFromRoute)->first();

            if (!$barangay) {
                return 'View and manage consultation records across all barangays';
            }
            return 'View and manage consultation records across barangay ' . $barangay->name;
        }
        return 'View and manage consultation records across all barangays';
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        $barangayFromRoute = request()->route('barangay');

        if ($barangayFromRoute === 'all') {
            return $query;
        }

        return $query
            ->with(['patient.barangay'])
            ->whereHas('patient', function ($patientQuery) use ($barangayFromRoute) {
                $patientQuery->where('barangay_id', $barangayFromRoute ?? Auth::user()->barangay_id);
            });
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

    //         $barangays = Barangay::all();

    //         foreach ($barangays as $barangay) {
    //             $tabs[$barangay->id] = Tab::make($barangay->name)
    //                 ->modifyQueryUsing(function ($query) use ($barangay) {
    //                     return $query->whereHas('patient', function ($query) use ($barangay) {
    //                         $query->where('barangay_id', $barangay->id);
    //                     })->latest();
    //                 })
    //                 ->badge(fn () => $this->getModel()::whereHas('patient', function ($query) use ($barangay) {
    //                     $query->where('barangay_id', $barangay->id);
    //                 })->count());
    //         }

    //         return $tabs;
    //     }

    //     return [];
    // }
}