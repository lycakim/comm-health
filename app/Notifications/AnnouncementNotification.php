<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;  // ← This is the correct one
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AnnouncementNotification extends Notification  // ← Extending Notification, not DatabaseNotification
{
    use Queueable;

    public function __construct(public $announcement)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title('New Announcement')
            ->body($this->announcement->title ?? 'A new announcement has been posted.')
            ->icon('heroicon-o-megaphone')
            ->success()
            ->getDatabaseMessage();
    }
}