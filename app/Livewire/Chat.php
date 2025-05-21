<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Message;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Chat extends Component
{
    use WithPagination;

    public $selectedUser = null;
    public $users = [];
    public $message = '';
    public $searchTerm = '';
    public $unreadMessageCounts = [];

    protected $listeners = ['refreshChat' => '$refresh'];
    
    protected $rules = [
        'message' => 'required|string|max:1000',
    ];

    public function mount()
    {
        $this->loadUsers();
        $this->calculateUnreadCounts();
    }

    public function loadUsers()
    {
        $this->users = User::where('id', '!=', Auth::id())
            ->when($this->searchTerm, function ($query) {
                return $query->where('name', 'like', '%' . $this->searchTerm . '%');
            })
            ->get();
    }
    
    public function calculateUnreadCounts()
    {
        $this->unreadMessageCounts = [];
        foreach ($this->users as $user) {
            $count = Message::where('sender_id', $user->id)
                ->where('receiver_id', Auth::id())
                ->where('read', false)
                ->count();
            $this->unreadMessageCounts[$user->id] = $count;
        }
    }
    
    public function selectUser($userId)
    {
        $this->selectedUser = User::where('id', $userId)->first();
        
        // Mark messages from this user as read
        Message::where('sender_id', $userId)
            ->where('receiver_id', Auth::id())
            ->where('read', false)
            ->update(['read' => true]);
            
        $this->calculateUnreadCounts();
        $this->resetPage();
    }
    
    public function sendMessage()
    {
        $this->validate();
        
        if (!$this->selectedUser) {
            return;
        }
        
        Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->selectedUser->id,
            'content' => $this->message,
            'read' => false,
        ]);
        
        $this->message = '';
        // $this->dispatchBrowserEvent('message-sent');
    }
    
    public function getMessagesProperty()
    {
        if (!$this->selectedUser) {
            return collect();
        }
        
        return Message::where(function ($query) {
                $query->where('sender_id', Auth::id())
                    ->where('receiver_id', $this->selectedUser->id);
            })
            ->orWhere(function ($query) {
                $query->where('sender_id', $this->selectedUser->id)
                    ->where('receiver_id', Auth::id());
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function render()
    {
        return view('livewire.chat', [
            'messages' => $this->messages,
        ]);
    }

    public function updatedSearchTerm()
    {
        $this->loadUsers();
    }
}