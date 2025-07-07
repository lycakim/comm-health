<?php

namespace App\Filament\Resources\ConsulltationReferralResource\Pages;

use App\Filament\Resources\ConsulltationReferralResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConsulltationReferrals extends ListRecords
{
    protected static string $resource = ConsulltationReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
