<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Sent when a bulk patient import job finishes. Not queued so it is written
 * to the database in the same process as the job (user sees it even if
 * the queue worker stops after the job).
 */
class ImportCompletedNotification extends Notification
{
    public function __construct(
        protected string $title,
        protected string $body,
        protected string $icon = 'heroicon-o-check-circle',
        protected string $iconColor = 'success',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon,
            'iconColor' => $this->iconColor,
            'duration' => 10000,
            'actions' => [],
        ];
    }
}
