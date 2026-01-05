<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;

class MessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected int $senderId,
        protected string $senderName,
        protected string $messagePreview,
        protected int $chatId,
        protected int $receiverId
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('New message from ' . $this->senderName)
            ->body($this->messagePreview)
            ->icon('heroicon-o-chat-bubble-left-right')
            ->iconColor('success')
            ->actions([
                Action::make('open')
                    ->label('Open Chat')
                    ->button()
                    ->dispatch('open-conversation-from-notification', ['userId' => $this->senderId])
            ])
            ->getDatabaseMessage();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'message',
            'sender_id' => $this->senderId,
            'sender_name' => $this->senderName,
            'message_preview' => $this->messagePreview,
            'chat_id' => $this->chatId,
            'receiver_id' => $this->receiverId,
        ]);
    }
}