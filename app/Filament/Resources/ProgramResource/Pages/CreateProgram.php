<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use App\Models\User;
use Filament\Actions;
use App\Enums\RoleEnum;
use App\Models\Patient;
use App\Services\SemaphoreService;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProgramResource;
use App\Notifications\AnnouncementNotification;

class CreateProgram extends CreateRecord
{
    protected static string $resource = ProgramResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userId = Auth::id();
        $data['coordinator'] = $userId;
        $data['created_by'] = $userId;

        // BHW/Midwife: force barangay to their assigned barangay
        $user = Auth::user();
        if (in_array($user->role, [\App\Enums\RoleEnum::BHW, \App\Enums\RoleEnum::MIDWIFE])) {
            $data['barangay_id'] = $user->barangay_id ?? $user->barangays()->first()?->id;
        }

        return $data;
    }
    
    protected function afterCreate(): void
    {
        $recipients = User::whereIn('role', [
            RoleEnum::BHW->value,
            RoleEnum::MIDWIFE->value,
        ])->whereHas('barangays', function ($query) {
            $query->where('barangays.id', $this->getRecord()->barangay_id);
        })->get();

        $record = $this->getRecord();

        foreach ($recipients as $recipient) {
            $recipient->notify(new AnnouncementNotification([
                'type' => 'program',
                'title' => 'New Program Created',
                'message' => 'A new program "' . $record->name . '" has been added.',
                'icon' => 'heroicon-o-cube',
                'status' => 'info',
            ]));
        }
    }
}