<?php

namespace App\Filament\Resources\BarangayResource\Pages;

use App\Filament\Resources\BarangayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\HasDashboardBreadcrumb;
use App\Filament\Concerns\HasBackAction;

class EditBarangay extends EditRecord
{
    use HasDashboardBreadcrumb;
    use HasBackAction;

    protected static string $resource = BarangayResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
            Actions\DeleteAction::make(),
        ];
    }
}