<?php

namespace App\Filament\Resources\AnnouncementResource\Pages;

use App\Models\User;
use Filament\Actions;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Notifications\AnnouncementNotification;
use App\Filament\Resources\AnnouncementResource;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function afterCreate(): void
    {
        $recipients = User::whereIn('role', [
            RoleEnum::BHW->value, 
            RoleEnum::MIDWIFE->value
        ])->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new AnnouncementNotification([
                'type' => 'announcement',
                'title' => 'New Announcement',
                'message' => $this->record->title ?? 'A new announcement has been posted.',
                'icon' => 'heroicon-o-megaphone',
                'status' => 'success',
            ]));
        }
    }
}