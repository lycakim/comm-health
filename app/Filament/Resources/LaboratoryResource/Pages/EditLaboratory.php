<?php

namespace App\Filament\Resources\LaboratoryResource\Pages;

use App\Filament\Resources\LaboratoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\HasDashboardBreadcrumb;
use App\Filament\Concerns\HasBackAction;

class EditLaboratory extends EditRecord
{
    use HasDashboardBreadcrumb;
    use HasBackAction;

    protected static string $resource = LaboratoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
