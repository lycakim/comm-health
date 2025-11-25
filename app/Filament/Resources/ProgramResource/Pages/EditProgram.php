<?php

namespace App\Filament\Resources\ProgramResource\Pages;

use App\Models\User;
use Filament\Actions;
use App\Enums\RoleEnum;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProgramResource;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AnnouncementNotification;

class EditProgram extends EditRecord
{
    protected static string $resource = ProgramResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        
        $recipients = User::whereIn('role', [
            RoleEnum::BHW->value,
            RoleEnum::MIDWIFE->value,
        ])
        ->whereHas('barangays', function ($query) use ($record) {
            $query->where('barangays.id', $record->barangay_id);
        })
        ->get();

        // Dispatch to queue if many recipients
        if ($recipients->count() > 10) {
            foreach ($recipients as $recipient) {
                $recipient->notify(
                    (new AnnouncementNotification([
                        'type' => 'program',
                        'title' => 'Program Updated',
                        'message' => $record->name . ' has been updated.',
                        'icon' => 'heroicon-o-cube',
                        'status' => 'info',
                    ]))->delay(now()->addSeconds(5))
                );
            }
        } else {
            Notification::send($recipients, new AnnouncementNotification([
                'type' => 'program',
                'title' => 'Program Updated',
                'message' => $record->name . ' has been updated.',
                'icon' => 'heroicon-o-cube',
                'status' => 'info',
            ]));
        }
    }
}