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
        // Only show button if user is not MHO AND has assigned barangay
        if (!Auth::user()->isMHO() && Auth::user()->barangays()->count() > 0) {
            return [
                Actions\CreateAction::make()
                    ->icon('heroicon-o-plus'),
            ];
        }
        
        // Return empty array to hide the create button
        return [];
    }

    // IMPORTANT: Override this method to control the default create action
    protected static bool $shouldRegisterNavigation = true;

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