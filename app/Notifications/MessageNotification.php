<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Filament\Notifications\Notification as FilamentNotification;

class MessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected int $senderId,
        protected string $senderName,
        protected string $messagePreview,
        protected int $chatId
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('You have a new message from ' . $this->senderName)
            ->line($this->messagePreview)
            ->action('View Message', url('/'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sender_id' => $this->senderId,
            'sender_name' => $this->senderName,
            'message_preview' => $this->messagePreview,
            'chat_id' => $this->chatId,
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $notification = FilamentNotification::make()
            ->title('New message from ' . $this->senderName)
            ->body($this->messagePreview)
            ->success()
            ->icon('heroicon-o-chat-bubble-left-right')
            ->actions([
                \Filament\Notifications\Actions\Action::make('open')
                    ->label('Open Chat')
                    ->button()
                    ->dispatch('open-conversation-from-notification', ['userId' => $this->senderId]),
            ]);

        $databaseMessage = $notification->getDatabaseMessage();
        
        // Add our custom data for reference
        $databaseMessage['sender_id'] = $this->senderId;
        $databaseMessage['chat_id'] = $this->chatId;
        $databaseMessage['type'] = 'message';
        
        return $databaseMessage;
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'message',
            'sender_id' => $this->senderId,
            'sender_name' => $this->senderName,
            'message_preview' => $this->messagePreview,
            'chat_id' => $this->chatId,
            'data' => [
                'sender_id' => $this->senderId,
                'sender_name' => $this->senderName,
                'message_preview' => $this->messagePreview,
                'chat_id' => $this->chatId,
            ],
        ]);
    }
}

