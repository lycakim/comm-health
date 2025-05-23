<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['barangay_id']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $barangayId = $this->form->getState()['barangay_id'];
        if ($barangayId) {
            $this->record->barangays()->attach($barangayId);
        }
    }
}