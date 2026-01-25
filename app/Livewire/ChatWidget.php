<?php

namespace App\Livewire;

use App\Models\Chat;
use App\Models\User;
use Livewire\Component;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification as FilamentNotification;

class ChatWidget extends Component
{
    public array $messages = [];
    public array $users = [];
    public array $unreadCounts = [];
    public ?int $selectedUserId = null;
    public string $message = '';
    public bool $isChatOpen = false;
    public bool $hasUnreadMessages = false;
    public string $viewMode = 'users'; // 'users' or 'messages'

    public function mount(): void
    {
        $this->loadUsers();
        $this->calculateUnreadCounts();
        
        // Start with user list view, no user selected initially
        $this->viewMode = 'users';
    }

    public function getListeners(): array
    {
        return [
            'message-received-from-echo' => 'handleIncomingMessage',
            'open-chat-with-user' => 'openChatFromNotification',
            'open-conversation-from-notification' => 'openConversationFromNotification',
        ];
    }

    public function handleIncomingMessage($data = []): void
    {
        // Extract chat data - handle both array and object formats
        $chat = is_array($data) ? ($data['chat'] ?? $data) : ($data->chat ?? null);
        if (!$chat) {
            return;
        }
        
        $senderId = is_array($chat) ? ($chat['sender_id'] ?? null) : ($chat->sender_id ?? null);
        $receiverId = is_array($chat) ? ($chat['receiver_id'] ?? null) : ($chat->receiver_id ?? null);
        $senderName = is_array($chat) ? ($chat['sender_name'] ?? 'Someone') : ($chat->sender->name ?? 'Someone');
        $message = is_array($chat) ? ($chat['message'] ?? '') : ($chat->message ?? '');
        
        // Determine if this message is for the current user
        $isForCurrentUser = $receiverId == Auth::id();
        
        if (!$isForCurrentUser || !$senderId) {
            return;
        }
        
        // Update unread counts
        $this->calculateUnreadCounts();
        
        // Check if we're already viewing this conversation
        $isCurrentConversation = $this->isChatOpen && 
                                  $this->selectedUserId == $senderId && 
                                  $this->viewMode === 'messages';
        
        if ($isCurrentConversation) {
            // Already viewing this conversation - just reload messages
            $this->loadMessages();
            $this->dispatch('scroll-to-bottom');
        } else {
            // Not viewing this conversation - show notification and update UI
            // Show Filament notification with clickable action
            FilamentNotification::make()
                ->title('New message from ' . $senderName)
                ->body($message)
                ->success()
                ->duration(6000)
                ->icon('heroicon-o-chat-bubble-left-right')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('open')
                        ->label('Open Chat')
                        ->button()
                        ->dispatch('open-conversation-from-notification', ['userId' => $senderId]),
                ])
                ->send();
            
            // Auto-open chat widget and switch to sender's conversation
            // Force chat to open first - this is critical for the UI to show
            $this->isChatOpen = true;
            
            // Select the user (this will switch to messages view and load messages)
            $this->selectedUserId = $senderId;
            $this->viewMode = 'messages';
            $this->loadMessages();
            $this->hasUnreadMessages = false;
            
            // Mark messages as read
            $this->markMessagesAsRead($senderId);
            
            // Dispatch event with the conversation ID for Echo subscription
            $this->dispatch('conversation-changed', conversationId: $this->getConversationId());
        }
        
        // Play notification sound
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

        // Ensure chat is open first
        $this->isChatOpen = true;
        
        $this->selectedUserId = $userId;
        $this->viewMode = 'messages';
        $this->loadMessages();
        $this->hasUnreadMessages = false;
        
        // Mark messages from this user as read
        $this->markMessagesAsRead($userId);
        
        // Dispatch event with the conversation ID
        $this->dispatch('conversation-changed', conversationId: $this->getConversationId());
    }

    public function showUserList(): void
    {
        $this->viewMode = 'users';
        $this->selectedUserId = null;
        $this->messages = [];
        $this->calculateUnreadCounts();
    }

    public function openChatFromNotification(int $userId): void
    {
        $this->selectUser($userId);
    }

    public function openConversationFromNotification($data = []): void
    {
        // Handle different data formats
        if (is_array($data)) {
            $userId = $data['userId'] ?? $data[0]['userId'] ?? null;
        } else {
            $userId = $data;
        }
        
        if ($userId) {
            $this->selectUser((int)$userId);
        }
    }

    protected function loadUsers(): void
    {
        $this->users = User::where('id', '!=', Auth::id())
            ->where('role', '!=', 'resident')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($user) {
                return [$user->id => $user->name];
            })
            ->toArray();
    }

    public function calculateUnreadCounts(): void
    {
        // Optimize: Use single aggregated query instead of N+1 queries
        $this->unreadCounts = Chat::where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->whereIn('sender_id', array_keys($this->users))
            ->groupBy('sender_id')
            ->selectRaw('sender_id, COUNT(*) as count')
            ->pluck('count', 'sender_id')
            ->toArray();
        
        // Ensure all users have a count (even if 0)
        foreach ($this->users as $userId => $userName) {
            if (!isset($this->unreadCounts[$userId])) {
                $this->unreadCounts[$userId] = 0;
            }
        }
        
        // Update hasUnreadMessages based on total count
        $this->hasUnreadMessages = $this->getTotalUnreadCount() > 0;
    }

    public function getTotalUnreadCount(): int
    {
        return array_sum($this->unreadCounts);
    }

    public function markMessagesAsRead(int $userId): void
    {
        Chat::where('sender_id', $userId)
            ->where('receiver_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        $this->calculateUnreadCounts();
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
                'is_mine' => $chat->sender_id === Auth::id(),
                'created_at' => $chat->created_at
                    ->timezone('Asia/Manila')
                    ->format('g:i A'),
                'date' => $chat->created_at
                    ->timezone('Asia/Manila')
                    ->format('F j, Y'),
            ])
            ->groupBy('date')
            ->toArray();
        // logger($this->messages);
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