<?php

namespace App\Filament\Resources\PatientResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\PatientResource;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->color('gray'),
            // Actions\Action::make('create_referral')
            //     ->label('Create Referral')
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