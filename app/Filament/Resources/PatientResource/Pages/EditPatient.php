<?php

namespace App\Filament\Resources\PatientResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Resources\PatientResource;
use App\Filament\Concerns\HasDashboardBreadcrumb;
use App\Filament\Concerns\HasBackAction;

class EditPatient extends EditRecord
{
    use HasDashboardBreadcrumb;
    use HasBackAction;

    protected static string $resource = PatientResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getBackAction(),
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