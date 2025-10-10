<?php

namespace App\Livewire;

use App\Models\Chat;
use App\Models\User;
use Livewire\Component;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;

class ChatWidget extends Component
{
    public array $messages = [];
    public array $users = [];
    public ?int $selectedUserId = null;
    public string $message = '';
    public bool $isChatOpen = false;
    public bool $hasUnreadMessages = false;

    public function mount(): void
    {
        $this->loadUsers();

        if (!$this->selectedUserId) {
            // If no user is selected, default to the first available user
            $this->selectedUserId = User::where('id', '!=', Auth::id())
                ->orderBy('created_at', 'desc')
                ->value('id');
        }
        
        if ($this->selectedUserId) {
            $this->loadMessages();
            // Dispatch initial conversation ID for Echo subscription
            $this->dispatch('conversation-changed', conversationId: $this->getConversationId());
        }
    }

    public function getListeners(): array
    {
        return [
            'message-received-from-echo' => 'handleIncomingMessage',
        ];
    }

    public function handleIncomingMessage(): void
    {
        // Reload messages when receiving a broadcast
        $this->loadMessages();
        
         // Open chat window if it's closed
        if (!$this->isChatOpen) {
            $this->isChatOpen = true;
            $this->hasUnreadMessages = true;
        }
        
        // Dispatch browser event to scroll to bottom
        $this->dispatch('scroll-to-bottom');
        
        // Optional: Play notification sound
        $this->dispatch('play-notification-sound');
    }

    public function toggleChat(): void
    {
        $this->isChatOpen = !$this->isChatOpen;
        
        // Mark as read when opening
        if ($this->isChatOpen) {
            $this->hasUnreadMessages = false;
        }
    }

    public function selectUser(int $userId): void
    {
        // Prevent selecting yourself
        if ($userId === Auth::id()) {
            return;
        }

        $this->selectedUserId = $userId;
        $this->loadMessages();
        $this->isChatOpen = true;
        $this->hasUnreadMessages = false; // Mark as read when user opens chat
        
        // Dispatch event with the conversation ID
        $this->dispatch('conversation-changed', conversationId: $this->getConversationId());
    }

    protected function loadUsers(): void
    {
        $this->users = User::where('id', '!=', Auth::id())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->message)) || !$this->selectedUserId) {
            return;
        }

        // Prevent sending to yourself
        if ($this->selectedUserId === Auth::id()) {
            return;
        }

        $chat = Chat::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->selectedUserId,
            'message' => trim($this->message),
        ]);

        // Load relationships before broadcasting
        $chat->load('sender', 'receiver');

        // Clear message first for better UX
        $this->message = '';

        // Load messages to show the new one immediately
        $this->loadMessages();
        
        // Broadcast to other users
        broadcast(new MessageSent($chat))->toOthers();

        // Dispatch event for scrolling
        $this->dispatch('message-sent');
    }

    public function loadMessages(): void
    {
        if (!$this->selectedUserId) {
            $this->messages = [];
            return;
        }

        $this->messages = Chat::where(function ($query) {
                $query->where('sender_id', Auth::id())
                      ->where('receiver_id', $this->selectedUserId);
            })
            ->orWhere(function ($query) {
                $query->where('sender_id', $this->selectedUserId)
                      ->where('receiver_id', Auth::id());
            })
            ->with(['sender:id,name', 'receiver:id,name'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($chat) => [
                'id' => $chat->id,
                'message' => $chat->message,
                'sender_id' => $chat->sender_id,
                'sender_name' => $chat->sender->name,
                'receiver_id' => $chat->receiver_id,
                'created_at' => $chat->created_at
                    ->timezone('Asia/Manila')
                    ->format('M j, Y g:i A'),
                'is_mine' => $chat->sender_id === Auth::id(),
            ])
            ->toArray();
    }

    public function getConversationId(): string
    {
        if (!$this->selectedUserId) {
            return '';
        }
        
        $ids = [Auth::id(), $this->selectedUserId];
        sort($ids);
        return implode('-', $ids);
    }
    
    public function render()
    {
        return view('livewire.chat-widget');
    }
}