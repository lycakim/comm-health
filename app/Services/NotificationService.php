<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\DatabaseNotification;
use App\Notifications\BroadcastNotification;
use Filament\Notifications\Notification as FilamentNotification;

class NotificationService
{
    /**
     * Send a database notification
     */
    public function sendDatabaseNotification(
        User $user, 
        string $title, 
        ?string $body = null,
        ?string $icon = null,
        ?string $iconColor = null,
        array $actions = []
    ): void {
        $user->notify(new DatabaseNotification(
            $title,
            $body,
            $icon,
            $iconColor,
            6000,
            $actions
        ));
        
        // Optional: Also show a Filament notification for immediate feedback
        FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->icon($icon ?? 'heroicon-o-bell')
            ->iconColor($iconColor ?? 'primary')
            ->actions($actions)
            ->send();
    }
    
    /**
     * Send a broadcast notification using Reverb
     */
    public function sendBroadcastNotification(
        User $user, 
        string $title, 
        ?string $body = null,
        ?string $icon = null,
        ?string $iconColor = null,
        array $actions = []
    ): void {
        // This will use the configured broadcast driver (Reverb)
        $user->notify(new BroadcastNotification(
            $title,
            $body,
            $icon,
            $iconColor,
            6000,
            $actions
        ));
    }
    
    /**
     * Send notification to all channels
     */
    public function sendAllNotifications(
        User $user, 
        string $title, 
        ?string $body = null,
        ?string $icon = null,
        ?string $iconColor = null,
        array $actions = []
    ): void {
        $this->sendDatabaseNotification($user, $title, $body, $icon, $iconColor, $actions);
        $this->sendBroadcastNotification($user, $title, $body, $icon, $iconColor, $actions);
    }
}