<?php

namespace App\Filament\Resources\ConsulltationReferralResource\Pages;

use App\Filament\Resources\ConsulltationReferralResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConsulltationReferral extends EditRecord
{
    protected static string $resource = ConsulltationReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
