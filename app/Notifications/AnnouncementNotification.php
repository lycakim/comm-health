<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification
{
    use Queueable;

    /**
     * $data accepts:
     * [
     *   'type' => 'announcement' | 'program' | 'custom',
     *   'title' => 'New Announcement',
     *   'message' => 'A new announcement has been posted.',
     *   'icon' => 'heroicon-o-bell',
     *   'status' => 'success' | 'info' | 'warning' | 'danger',
     * ]
     */
    public function __construct(public array $data)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $notification = \Filament\Notifications\Notification::make()
            ->title($this->data['title'] ?? 'System Update')
            ->body($this->data['message'] ?? '')
            ->icon($this->data['icon'] ?? 'heroicon-o-information-circle');

        // Apply status (success, warning, danger, info)
        if (!empty($this->data['status'])) {
            $notification->{$this->data['status']}();
        }

        return $notification->getDatabaseMessage();
    }
}