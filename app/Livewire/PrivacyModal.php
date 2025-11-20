<?php

namespace App\Livewire;

use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class PrivacyModal extends Component
{
    public $showModal = false;

    public function mount()
    {
        $user = Auth::user();
        
        if (!$user || $user->isAdmin()) {
            $this->showModal = false;
            return;
        }
        
        // Show modal if user hasn't accepted privacy today
        $hasAcceptedToday = $user->privacy_accepted_at && $user->privacy_accepted_at->isToday();
        $this->showModal = !$hasAcceptedToday;
    }

    public function acceptPrivacy()
    {
        $user = Auth::user();
        
        if ($user && !$user->isAdmin()) {
            $user->update([
                'privacy_accepted_at' => now(),
            ]);
            
            $this->showModal = false;
            
            Notification::make()
                ->title('Privacy Notice Accepted')
                ->body('You have accepted the privacy notice.')
                ->success()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.privacy-modal');
    }
}