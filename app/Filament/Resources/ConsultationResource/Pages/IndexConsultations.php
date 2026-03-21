<?php

namespace App\Filament\Resources\ConsultationResource\Pages;

use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ConsultationResource;
use Filament\Actions;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class IndexConsultations extends ListRecords
{
    use HasDashboardBreadcrumb;

    protected static string $resource = ConsultationResource::class;

    public function mount(): void
    {
        parent::mount();
        
        if (Auth::user()->isMHO() || Auth::user()->isAdmin()) {
            redirect()->to(ConsultationResource::getUrl('all'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }
}