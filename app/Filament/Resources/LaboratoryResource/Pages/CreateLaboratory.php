<?php

namespace App\Filament\Resources\LaboratoryResource\Pages;

use App\Filament\Resources\LaboratoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Filament\Concerns\HasDashboardBreadcrumb;

class CreateLaboratory extends CreateRecord
{
    use HasDashboardBreadcrumb;

    protected static string $resource = LaboratoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
