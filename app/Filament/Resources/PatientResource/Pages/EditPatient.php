<?php

namespace App\Filament\Resources\PatientResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\PatientResource;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            // Actions\Action::make('create_referral')
            //     ->label('Create Referral')
            // Actions\DeleteAction::make(),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->record->account_user_id) {
            return 'This patient already has an account: ' . $this->record->account->email . '.';
        }

        return null;
    }
}