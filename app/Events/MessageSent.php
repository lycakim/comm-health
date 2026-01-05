<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use App\Notifications\MessageNotification;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Chat $chat)
    {
        // Ensure relationships are loaded
        if (!$chat->relationLoaded('sender')) {
            $chat->load('sender');
        }
        if (!$chat->relationLoaded('receiver')) {
            $chat->load('receiver');
        }
        
        // Send notification to receiver
        $receiver = $chat->receiver;
        if ($receiver && $chat->sender) {
            $messagePreview = strlen($chat->message) > 50 
                ? substr($chat->message, 0, 50) . '...' 
                : $chat->message;
            
            $receiver->notify(new MessageNotification(
                senderId: $chat->sender_id,
                senderName: $chat->sender->name ?? 'Unknown',
                messagePreview: $messagePreview,
                chatId: $chat->id,
                receiverId: $chat->receiver_id
            ));
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chat->getConversationId()),
        ];
    }

    public function broadcastWith(): array
    {
        // Ensure sender is loaded
        if (!$this->chat->relationLoaded('sender')) {
            $this->chat->load('sender');
        }
        
        return [
            'chat' => [
                'id' => $this->chat->id,
                'message' => $this->chat->message,
                'sender_id' => $this->chat->sender_id,
                'sender_name' => $this->chat->sender->name ?? 'Unknown',
                'receiver_id' => $this->chat->receiver_id,
                'created_at' => now()->timezone('Asia/Manila')->format('M j, Y g:i A'),
            ]
        ];
    }
    
    public function broadcastAs()
    {
        return 'MessageSent';
    }
}