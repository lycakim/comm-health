<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use App\Filament\Resources\ProgramResource;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Concerns\HasDashboardBreadcrumb;
use App\Filament\Concerns\HasBackAction;

class ViewProgram extends ViewRecord
{
    use HasDashboardBreadcrumb;
    use HasBackAction;

    protected static string $resource = ProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
        ];
    }
}
